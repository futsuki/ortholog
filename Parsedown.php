<?php
class Parsedown
{
    function text($text)
    {
        $Elements = $this->textElements($text);
        $markup = $this->elements($Elements);
        $markup = $this->handleRemainingText($markup);
        return $markup;
    }

    protected function textElements($text)
    {
        $text = str_replace(array("\r\n", "\r"), "\n", $text);
        $text = trim($text, "\n");
        $lines = explode("\n", $text);
        return $this->linesElements($lines);
    }

    protected function setExtent(&$value)
    {
        if ( ! $this->safeMode)
        {
            return;
        }
        $value = '';
    }

    protected function handleRemainingText($text)
    {
        $text = preg_replace('/\n[ ]{0,3}\n/', "\n\n", $text);
        return trim($text);
    }

    protected function linesElements(array $lines)
    {
        $Elements = array();
        $CurrentBlock = null;

        foreach ($lines as $line)
        {
            if (chop($line) === '')
            {
                if (isset($CurrentBlock))
                {
                    $CurrentBlock['interrupted'] = true;
                }
                continue;
            }

            if (strpos($line, "\t") !== false)
            {
                $parts = explode("\t", $line);
                $line = $parts[0];
                unset($parts[0]);
                foreach ($parts as $part)
                {
                    $shortage = 4 - mb_strlen($line, 'utf-8') % 4;
                    $line .= str_repeat(' ', $shortage);
                    $line .= $part;
                }
            }

            $indent = 0;
            while (isset($line[$indent]) and $line[$indent] === ' ')
            {
                $indent++;
            }
            $text = $indent > 0 ? substr($line, $indent) : $line;
            $Line = array('body' => $line, 'indent' => $indent, 'text' => $text);

            if (isset($CurrentBlock['continuable']))
            {
                $Block = $this->{'block'.$CurrentBlock['type'].'Continue'}($Line, $CurrentBlock);
                if (isset($Block))
                {
                    $CurrentBlock = $Block;
                    continue;
                }
                else
                {
                    if ($this->isBlockCompletable($CurrentBlock['type']))
                    {
                        $CurrentBlock = $this->{'block'.$CurrentBlock['type'].'Complete'}($CurrentBlock);
                    }
                }
            }

            $marker = $text[0];
            $blockTypes = $this->unmarkedBlockTypes;
            if (isset($this->BlockTypes[$marker]))
            {
                foreach ($this->BlockTypes[$marker] as $blockType)
                {
                    $blockTypes[] = $blockType;
                }
            }

            foreach ($blockTypes as $blockType)
            {
                $Block = $this->{'block'.$blockType}($Line, $CurrentBlock);
                if (isset($Block))
                {
                    $Block['type'] = $blockType;
                    if ( ! isset($Block['identified']))
                    {
                        $Blocks[] = $CurrentBlock;
                        $Block['identified'] = true;
                    }
                    if ($this->isBlockContinuable($blockType))
                    {
                        $Block['continuable'] = true;
                    }
                    $CurrentBlock = $Block;
                    continue 2;
                }
            }

            if (isset($CurrentBlock) and ! isset($CurrentBlock['type']) and ! isset($CurrentBlock['interrupted']))
            {
                $CurrentBlock['element']['text'] .= "\n".$text;
            }
            else
            {
                $Blocks[] = $CurrentBlock;
                $CurrentBlock = $this->paragraph($Line);
                $CurrentBlock['identified'] = true;
            }
        }

        if (isset($CurrentBlock['continuable']) and $this->isBlockCompletable($CurrentBlock['type']))
        {
            $CurrentBlock = $this->{'block'.$CurrentBlock['type'].'Complete'}($CurrentBlock);
        }

        $Blocks[] = $CurrentBlock;
        unset($Blocks[0]);

        $Elements = array();
        foreach ($Blocks as $Block)
        {
            if (isset($Block['hidden']))
            {
                continue;
            }
            $Elements[] = isset($Block['markup']) ? array('rawHtml' => $Block['markup']) : $Block['element'];
        }

        return $Elements;
    }

    protected function isBlockContinuable($Type)
    {
        return method_exists($this, 'block'.$Type.'Continue');
    }

    protected function isBlockCompletable($Type)
    {
        return method_exists($this, 'block'.$Type.'Complete');
    }

    protected function blockCode($Line, $Block = null)
    {
        if (isset($Block) and ! isset($Block['type']) and ! isset($Block['interrupted']))
        {
            return;
        }
        if ($Line['indent'] >= 4)
        {
            $text = substr($Line['body'], 4);
            $Block = array(
                'element' => array(
                    'name' => 'pre',
                    'handler' => 'element',
                    'text' => array(
                        'name' => 'code',
                        'text' => $text,
                    ),
                ),
            );
            return $Block;
        }
    }

    protected function blockCodeContinue($Line, $Block)
    {
        if ($Line['indent'] >= 4)
        {
            if (isset($Block['interrupted']))
            {
                $Block['element']['text']['text'] .= "\n";
                unset($Block['interrupted']);
            }
            $Block['element']['text']['text'] .= "\n";
            $text = substr($Line['body'], 4);
            $Block['element']['text']['text'] .= $text;
            return $Block;
        }
    }

    protected function blockCodeComplete($Block)
    {
        $text = $Block['element']['text']['text'];
        $Block['element']['text']['text'] = $text;
        return $Block;
    }

    protected function blockComment($Line)
    {
        if ($this->markupEscaped or $this->safeMode)
        {
            return;
        }
        if (isset($Line['text'][3]) and $Line['text'][3] === '-' and $Line['text'][2] === '-' and $Line['text'][1] === '!')
        {
            $Block = array('markup' => $Line['body']);
            if (preg_match('/-->$/', $Line['text']))
            {
                $Block['closed'] = true;
            }
            return $Block;
        }
    }

    protected function blockCommentContinue($Line, array $Block)
    {
        if (isset($Block['closed']))
        {
            return;
        }
        $Block['markup'] .= "\n" . $Line['body'];
        if (preg_match('/-->$/', $Line['text']))
        {
            $Block['closed'] = true;
        }
        return $Block;
    }

    protected function blockFencedCode($Line)
    {
        if (preg_match('/^['.$Line['text'][0].']{3,}[ ]*([^`]+)?[ ]*$/', $Line['text'], $matches))
        {
            $Element = array('name' => 'code', 'text' => '');
            if (isset($matches[1]))
            {
                $class = 'language-'.trim($matches[1]);
                $Element['attributes'] = array('class' => $class);
            }
            $Block = array(
                'char' => $Line['text'][0],
                'element' => array(
                    'name' => 'pre',
                    'handler' => 'element',
                    'text' => $Element,
                ),
            );
            return $Block;
        }
    }

    protected function blockFencedCodeContinue($Line, $Block)
    {
        if (isset($Block['complete']))
        {
            return;
        }
        if (isset($Block['interrupted']))
        {
            $Block['element']['text']['text'] .= "\n";
            unset($Block['interrupted']);
        }
        if (preg_match('/^'.$Block['char'].'{3,}[ ]*$/', $Line['text']))
        {
            $Block['element']['text']['text'] = substr($Block['element']['text']['text'], 1);
            $Block['complete'] = true;
            return $Block;
        }
        $Block['element']['text']['text'] .= "\n".$Line['body'];
        return $Block;
    }

    protected function blockFencedCodeComplete($Block)
    {
        $text = $Block['element']['text']['text'];
        $Block['element']['text']['text'] = $text;
        return $Block;
    }

    protected function blockHeader($Line)
    {
        if (isset($Line['text'][1]))
        {
            $level = 1;
            while (isset($Line['text'][$level]) and $Line['text'][$level] === '#')
            {
                $level++;
            }
            if ($level > 6)
            {
                return;
            }
            $text = trim($Line['text'], '# ');
            $Block = array(
                'element' => array(
                    'name' => 'h' . min(6, $level),
                    'text' => $text,
                    'handler' => 'line',
                ),
            );
            return $Block;
        }
    }

    protected function blockSetextHeader($Line, array $Block = null)
    {
        if (!isset($Block) or isset($Block['type']) or isset($Block['interrupted']))
        {
            return;
        }
        if (chop($Line['text'], $Line['text'][0]) === '')
        {
            $Block['element']['name'] = $Line['text'][0] === '=' ? 'h1' : 'h2';
            return $Block;
        }
    }

    protected function blockHr($Line)
    {
        if (preg_match('/^[ ]{0,3}([-*_])([ ]{0,2}\1){2,}[ ]*$/', $Line['text']))
        {
            $Block = array('element' => array('name' => 'hr'));
            return $Block;
        }
    }

    protected function blockList($Line)
    {
        list($name, $pattern) = $Line['text'][0] <= '-' ? array('ul', '[*+-]') : array('ol', '[0-9]+[.]');
        if (preg_match('/^('.$pattern.'[ ]+)(.*)/', $Line['text'], $matches))
        {
            $Block = array(
                'indent' => $Line['indent'],
                'pattern' => $pattern,
                'element' => array(
                    'name' => $name,
                    'handler' => 'elements',
                ),
            );
            if ($name === 'ol')
            {
                $listStart = strstr($matches[0], '.', true);
                if ($listStart !== '1')
                {
                    $Block['element']['attributes'] = array('start' => $listStart);
                }
            }
            $Block['li'] = array(
                'name' => 'li',
                'handler' => 'li',
                'text' => ! empty($matches[2]) ? $matches[2] : '',
            );
            $Block['element']['elements'][] = & $Block['li'];
            return $Block;
        }
    }

    protected function blockListContinue($Line, array $Block)
    {
        if ($Block['indent'] === $Line['indent'] and preg_match('/^'.$Block['pattern'].'(?:[ ]+(.*))?$/', $Line['text'], $matches))
        {
            if (isset($Block['interrupted']))
            {
                $Block['li']['text'][] = '';
                $Block['loose'] = true;
                unset($Block['interrupted']);
            }
            unset($Block['li']);
            $text = isset($matches[1]) ? $matches[1] : '';
            $Block['li'] = array(
                'name' => 'li',
                'handler' => 'li',
                'text' => array($text),
            );
            $Block['element']['elements'][] = & $Block['li'];
            return $Block;
        }
        if ($Line['text'][0] === '[' and $this->blockReference($Line))
        {
            return $Block;
        }
        if ( ! isset($Block['interrupted']))
        {
            $text = preg_replace('/^[ ]{0,4}/', '', $Line['body']);
            $Block['li']['text'][] = $text;
            return $Block;
        }
        if ($Line['indent'] > 0)
        {
            $Block['li']['text'][] = '';
            $text = preg_replace('/^[ ]{0,4}/', '', $Line['body']);
            $Block['li']['text'][] = $text;
            unset($Block['interrupted']);
            return $Block;
        }
    }

    protected function blockListComplete(array $Block)
    {
        if (isset($Block['loose']))
        {
            foreach ($Block['element']['elements'] as &$li)
            {
                if (end($li['text']) !== '')
                {
                    $li['text'][] = '';
                }
            }
        }
        return $Block;
    }

    protected function blockQuote($Line)
    {
        if (preg_match('/^>[ ]?(.*)/', $Line['text'], $matches))
        {
            $Block = array(
                'element' => array(
                    'name' => 'blockquote',
                    'handler' => 'lines',
                    'text' => (array) $matches[1],
                ),
            );
            return $Block;
        }
    }

    protected function blockQuoteContinue($Line, array $Block)
    {
        if ($Line['text'][0] === '>' and preg_match('/^>[ ]?(.*)/', $Line['text'], $matches))
        {
            if (isset($Block['interrupted']))
            {
                $Block['element']['text'][] = '';
                unset($Block['interrupted']);
            }
            $Block['element']['text'][] = $matches[1];
            return $Block;
        }
        if ( ! isset($Block['interrupted']))
        {
            $Block['element']['text'][] = $Line['text'];
            return $Block;
        }
    }

    protected function blockReference($Line)
    {
        if (preg_match('/^\[(.+?)\]:[ ]*<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*$/', $Line['text'], $matches))
        {
            $id = strtolower($matches[1]);
            $Data = array('url' => $matches[2], 'title' => null);
            if (isset($matches[3]))
            {
                $Data['title'] = $matches[3];
            }
            $this->DefinitionData['Reference'][$id] = $Data;
            $Block = array('hidden' => true);
            return $Block;
        }
    }

    protected function blockRule($Line)
    {
        if (preg_match('/^(['.$Line['text'][0].'])([ ]*\1){2,}[ ]*$/', $Line['text']))
        {
            $Block = array('element' => array('name' => 'hr'));
            return $Block;
        }
    }

    protected function blockTable($Line, array $Block = null)
    {
        if (!isset($Block) or isset($Block['type']) or isset($Block['interrupted']))
        {
            return;
        }
        if (strpos($Block['element']['text'], '|') === false
            and strpos($Line['text'], '|') === false
            and strpos($Line['text'], ':') === false)
        {
            return;
        }
        if (chop($Line['text'], ' -:|') !== '')
        {
            return;
        }
        $alignments = array();
        $divider = $Line['text'];
        $divider = trim($divider);
        $divider = trim($divider, '|');
        $dividerCells = explode('|', $divider);
        foreach ($dividerCells as $dividerCell)
        {
            $dividerCell = trim($dividerCell);
            if ($dividerCell === '')
            {
                continue;
            }
            $alignment = null;
            if ($dividerCell[0] === ':')
            {
                $alignment = 'left';
            }
            if (substr($dividerCell, -1) === ':')
            {
                $alignment = $alignment === 'left' ? 'center' : 'right';
            }
            $alignments[] = $alignment;
        }
        $headerElements = array();
        $header = $Block['element']['text'];
        $header = trim($header);
        $header = trim($header, '|');
        $headerCells = explode('|', $header);
        foreach ($headerCells as $index => $headerCell)
        {
            $headerCell = trim($headerCell);
            $headerElement = array(
                'name' => 'th',
                'text' => $headerCell,
                'handler' => 'line',
            );
            if (isset($alignments[$index]))
            {
                $alignment = $alignments[$index];
                if ($alignment)
                {
                    $headerElement['attributes'] = array('style' => 'text-align: '.$alignment.';');
                }
            }
            $headerElements[] = $headerElement;
        }
        $Block = array(
            'alignments' => $alignments,
            'identified' => true,
            'element' => array(
                'name' => 'table',
                'handler' => 'elements',
            ),
        );
        $Block['element']['elements'][] = array(
            'name' => 'thead',
            'handler' => 'elements',
        );
        $Block['element']['elements'][] = array(
            'name' => 'tbody',
            'handler' => 'elements',
            'elements' => array(),
        );
        $Block['element']['elements'][0]['elements'][] = array(
            'name' => 'tr',
            'handler' => 'elements',
            'elements' => $headerElements,
        );
        return $Block;
    }

    protected function blockTableContinue($Line, array $Block)
    {
        if (isset($Block['interrupted']))
        {
            return;
        }
        if ($Line['text'][0] === '|' or strpos($Line['text'], '|'))
        {
            $elements = array();
            $row = $Line['text'];
            $row = trim($row);
            $row = trim($row, '|');
            preg_match_all('/(?:(\\\\[|])|[^|`]|`[^`]+`|`)+/', $row, $matches);
            foreach ($matches[0] as $index => $cell)
            {
                $cell = trim($cell);
                $element = array(
                    'name' => 'td',
                    'handler' => 'line',
                    'text' => $cell,
                );
                if (isset($Block['alignments'][$index]))
                {
                    $element['attributes'] = array('style' => 'text-align: '.$Block['alignments'][$index].';');
                }
                $elements[] = $element;
            }
            $Block['element']['elements'][1]['elements'][] = array(
                'name' => 'tr',
                'handler' => 'elements',
                'elements' => $elements,
            );
            return $Block;
        }
    }

    protected function blockTableComplete($Block)
    {
        return $Block;
    }

    protected function paragraph($Line)
    {
        $Block = array(
            'element' => array(
                'name' => 'p',
                'text' => $Line['text'],
                'handler' => 'line',
            ),
        );
        return $Block;
    }

    protected function inlineCode($Excerpt)
    {
        $marker = $Excerpt['text'][0];
        if (preg_match('/^('.$marker.'+)[ ]*(.+?)[ ]*(?<!'.$marker.')\1(?!'.$marker.')/s', $Excerpt['text'], $matches))
        {
            $text = $matches[2];
            $text = preg_replace("/[ ]*\n/", ' ', $text);
            return array(
                'extent' => strlen($matches[0]),
                'element' => array('name' => 'code', 'text' => $text),
            );
        }
    }

    protected function inlineEmailTag($Excerpt)
    {
        if (strpos($Excerpt['text'], '>') !== false and preg_match('/^<((mailto:)?\S+?@\S+?)>/i', $Excerpt['text'], $matches))
        {
            $url = $matches[1];
            if ( ! isset($matches[2]))
            {
                $url = 'mailto:' . $url;
            }
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'a',
                    'text' => $matches[1],
                    'attributes' => array('href' => $url),
                ),
            );
        }
    }

    protected function inlineEmphasis($Excerpt)
    {
        if ( ! isset($Excerpt['text'][1]))
        {
            return;
        }
        $marker = $Excerpt['text'][0];
        if ($Excerpt['text'][1] === $marker and preg_match($this->StrongRegex[$marker], $Excerpt['text'], $matches))
        {
            $emphasis = 'strong';
        }
        elseif (preg_match($this->EmRegex[$marker], $Excerpt['text'], $matches))
        {
            $emphasis = 'em';
        }
        else
        {
            return;
        }
        return array(
            'extent' => strlen($matches[0]),
            'element' => array(
                'name' => $emphasis,
                'handler' => 'line',
                'text' => $matches[1],
            ),
        );
    }

    protected function inlineEscapeSequence($Excerpt)
    {
        if (isset($Excerpt['text'][1]) and in_array($Excerpt['text'][1], $this->specialCharacters))
        {
            return array('markup' => $Excerpt['text'][1], 'extent' => 2);
        }
    }

    protected function inlineImage($Excerpt)
    {
        if ( ! isset($Excerpt['text'][1]) or $Excerpt['text'][1] !== '[')
        {
            return;
        }
        $Excerpt['text'] = substr($Excerpt['text'], 1);
        $Link = $this->inlineLink($Excerpt);
        if ($Link === null)
        {
            return;
        }
        $Inline = array(
            'extent' => $Link['extent'] + 1,
            'element' => array(
                'name' => 'img',
                'attributes' => array(
                    'src' => $Link['element']['attributes']['href'],
                    'alt' => $Link['element']['text'],
                ),
            ),
        );
        $Inline['element']['attributes'] += $Link['element']['attributes'];
        unset($Inline['element']['attributes']['href']);
        return $Inline;
    }

    protected function inlineLink($Excerpt)
    {
        $Element = array(
            'name' => 'a',
            'handler' => 'line',
            'nonEmpty' => true,
            'text' => null,
            'attributes' => array('href' => null, 'title' => null),
        );
        $extent = 0;
        $remainder = $Excerpt['text'];
        if (preg_match('/\[((?:[^][]++|(?R))*+)\]/', $remainder, $matches))
        {
            $Element['text'] = $matches[1];
            $extent += strlen($matches[0]);
            $remainder = substr($remainder, $extent);
        }
        else
        {
            return;
        }
        if (preg_match('/^[(]\s*+((?:[^ ()]++|[(][^ )]+[)])++)(?:[ ]+("[^"]*"|\'[^\']*\'))?\s*[)]/', $remainder, $matches))
        {
            $Element['attributes']['href'] = $matches[1];
            if (isset($matches[2]))
            {
                $Element['attributes']['title'] = substr($matches[2], 1, -1);
            }
            $extent += strlen($matches[0]);
        }
        else
        {
            if (preg_match('/^\s*\[(.*?)\]/', $remainder, $matches))
            {
                $definition = strlen($matches[1]) ? $matches[1] : $Element['text'];
                $definition = strtolower($definition);
                $extent += strlen($matches[0]);
            }
            else
            {
                $definition = strtolower($Element['text']);
            }
            if ( ! isset($this->DefinitionData['Reference'][$definition]))
            {
                return;
            }
            $Definition = $this->DefinitionData['Reference'][$definition];
            $Element['attributes']['href'] = $Definition['url'];
            $Element['attributes']['title'] = $Definition['title'];
        }
        return array('extent' => $extent, 'element' => $Element);
    }

    protected function inlineMarkup($Excerpt)
    {
        if ($this->markupEscaped or $this->safeMode or strpos($Excerpt['text'], '>') === false)
        {
            return;
        }
        if ($Excerpt['text'][1] === '/' and preg_match('/^<\/\w[\w-]*[ ]*>/s', $Excerpt['text'], $matches))
        {
            return array('markup' => $matches[0], 'extent' => strlen($matches[0]));
        }
        if ($Excerpt['text'][1] === '!' and preg_match('/^<!---?[^>-](?:-?[^-])*-->/s', $Excerpt['text'], $matches))
        {
            return array('markup' => $matches[0], 'extent' => strlen($matches[0]));
        }
        if ($Excerpt['text'][1] !== ' ' and preg_match('/^<\w[\w-]*(?:[ ]*'.$this->regexHtmlAttribute.')*[ ]*\/?>/s', $Excerpt['text'], $matches))
        {
            return array('markup' => $matches[0], 'extent' => strlen($matches[0]));
        }
    }

    protected function inlineSpecialCharacter($Excerpt)
    {
        if ($Excerpt['text'][0] === '&' and ! preg_match('/^&#?\w+;/', $Excerpt['text']))
        {
            return array('markup' => '&amp;', 'extent' => 1);
        }
        $SpecialCharacter = array('>' => 'gt', '<' => 'lt', '"' => 'quot');
        if (isset($SpecialCharacter[$Excerpt['text'][0]]))
        {
            return array('markup' => '&'.$SpecialCharacter[$Excerpt['text'][0]].';', 'extent' => 1);
        }
    }

    protected function inlineStrikethrough($Excerpt)
    {
        if ( ! isset($Excerpt['text'][1]))
        {
            return;
        }
        if ($Excerpt['text'][1] === '~' and preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $Excerpt['text'], $matches))
        {
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'del',
                    'text' => $matches[1],
                    'handler' => 'line',
                ),
            );
        }
    }

    protected function inlineUrl($Excerpt)
    {
        if ($this->urlsLinked !== true or ! isset($Excerpt['text'][2]) or $Excerpt['text'][2] !== '/')
        {
            return;
        }
        if (preg_match('/\bhttps?:[\/]{2}[^\s<]+\b\/*/ui', $Excerpt['context'], $matches, PREG_OFFSET_CAPTURE))
        {
            $url = $matches[0][0];
            return array(
                'extent' => strlen($matches[0][0]),
                'position' => $matches[0][1],
                'element' => array(
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => array('href' => $url),
                ),
            );
        }
    }

    protected function inlineUrlTag($Excerpt)
    {
        if (strpos($Excerpt['text'], '>') !== false and preg_match('/^<(\w+:\/{2}[^ >]+)>/i', $Excerpt['text'], $matches))
        {
            $url = $matches[1];
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => array('href' => $url),
                ),
            );
        }
    }

    protected function unmarkedText($text)
    {
        if ($this->breaksEnabled)
        {
            $text = preg_replace('/[ ]*\n/', "<br />\n", $text);
        }
        else
        {
            $text = preg_replace('/(?:[ ][ ]+|[ ]*\\\\)\n/', "<br />\n", $text);
            $text = str_replace(" \n", "\n", $text);
        }
        return $text;
    }

    protected function element(array $Element)
    {
        if ($this->safeMode)
        {
            $Element = $this->sanitiseElement($Element);
        }
        $markup = '<'.$Element['name'];
        if (isset($Element['attributes']))
        {
            foreach ($Element['attributes'] as $name => $value)
            {
                if ($value === null)
                {
                    continue;
                }
                $markup .= ' '.$name.'="'.self::escape($value).'"';
            }
        }
        $permitRawHtml = false;
        if (isset($Element['text']))
        {
            $text = $Element['text'];
        }
        elseif (isset($Element['rawHtml']))
        {
            $text = $Element['rawHtml'];
            $permitRawHtml = true;
        }
        if (isset($text))
        {
            $markup .= '>';
            if (!isset($Element['nonEmpty']) or $Element['nonEmpty'])
            {
                if (isset($Element['handler']))
                {
                    $markup .= $this->{$Element['handler']}($text);
                }
                else
                {
                    $markup .= $permitRawHtml ? $text : self::escape($text, true);
                }
            }
            $markup .= '</'.$Element['name'].'>';
        }
        elseif (isset($Element['elements']))
        {
            $markup .= '>' . $this->elements($Element['elements']) . '</'.$Element['name'].'>';
        }
        else
        {
            $markup .= ' />';
        }
        return $markup;
    }

    protected function lines($lines)
    {
        $Elements = $this->linesElements($lines);
        return $this->elements($Elements);
    }

    protected function elements(array $Elements)
    {
        $markup = '';
        foreach ($Elements as $Element)
        {
            $markup .= "\n" . $this->element($Element);
        }
        $markup .= "\n";
        return $markup;
    }

    protected function li($lines)
    {
        if (!is_array($lines)) {
            $lines = array($lines);
        }
        $Elements = $this->linesElements($lines);
        if (isset($this->DefinitionData['Reference']))
        {
            foreach ($Elements as &$Element)
            {
                if (isset($Element['rawHtml']))
                {
                    continue;
                }
                $Element = $this->element($Element);
            }
        }
        return $this->elements($Elements);
    }

    function line($text, $nonNestables = array())
    {
        return $this->handleRemainingText($this->lineElements($text, $nonNestables));
    }

    protected function lineElements($text, $nonNestables = array())
    {
        $Elements = array();
        $nonNestables = (empty($nonNestables)
            ? array()
            : array_combine($nonNestables, $nonNestables)
        );

        $markup = '';
        while ($excerpt = strpbrk($text, $this->inlineMarkerList))
        {
            $marker = $excerpt[0];
            $markerPosition = strpos($text, $marker);
            $Excerpt = array('text' => $excerpt, 'context' => $text);
            foreach ($this->InlineTypes[$marker] as $inlineType)
            {
                if (isset($nonNestables[$inlineType]))
                {
                    continue;
                }
                $Inline = $this->{'inline'.$inlineType}($Excerpt);
                if ( ! isset($Inline))
                {
                    continue;
                }
                if (isset($Inline['position']) and $Inline['position'] > $markerPosition)
                {
                    continue;
                }
                if ( ! isset($Inline['position']))
                {
                    $Inline['position'] = $markerPosition;
                }
                $unmarkedText = substr($text, 0, $Inline['position']);
                $markup .= $this->unmarkedText($unmarkedText);
                $markup .= isset($Inline['markup']) ? $Inline['markup'] : $this->element($Inline['element']);
                $text = substr($text, $Inline['position'] + $Inline['extent']);
                continue 2;
            }
            $unmarkedText = substr($text, 0, $markerPosition + 1);
            $markup .= $this->unmarkedText($unmarkedText);
            $text = substr($text, $markerPosition + 1);
        }
        $markup .= $this->unmarkedText($text);
        return $markup;
    }

    protected $safeMode = false;
    protected $safeLinksWhitelist = array(
        'http://', 'https://', 'ftp://', 'ftps://', 'mailto:',
        'data:image/png;base64,', 'data:image/gif;base64,', 'data:image/jpeg;base64,',
        'irc:', 'ircs:', 'git:', 'ssh:', 'news:', 'steam:',
    );

    protected function sanitiseElement(array $Element)
    {
        static $goodAttribute = '/^[a-zA-Z0-9][a-zA-Z0-9-_]*+$/';
        static $safeUrlNameToAtt = array('a' => 'href', 'img' => 'src');
        if (isset($safeUrlNameToAtt[$Element['name']]))
        {
            $Element = $this->filterUnsafeUrlInAttribute($Element, $safeUrlNameToAtt[$Element['name']]);
        }
        if ( ! empty($Element['attributes']))
        {
            foreach ($Element['attributes'] as $att => $val)
            {
                if ( ! preg_match($goodAttribute, $att))
                {
                    unset($Element['attributes'][$att]);
                }
                elseif (self::striAtStart($att, 'on'))
                {
                    unset($Element['attributes'][$att]);
                }
            }
        }
        return $Element;
    }

    protected function filterUnsafeUrlInAttribute(array $Element, $attribute)
    {
        foreach ($this->safeLinksWhitelist as $scheme)
        {
            if (self::striAtStart($Element['attributes'][$attribute], $scheme))
            {
                return $Element;
            }
        }
        $Element['attributes'][$attribute] = str_replace(':', '%3A', $Element['attributes'][$attribute]);
        return $Element;
    }

    protected static function escape($text, $allowQuotes = false)
    {
        return htmlspecialchars($text, $allowQuotes ? ENT_NOQUOTES : ENT_QUOTES, 'UTF-8');
    }

    protected static function striAtStart($string, $needle)
    {
        $len = strlen($needle);
        if ($len > strlen($string))
        {
            return false;
        }
        return strtolower(substr($string, 0, $len)) === strtolower($needle);
    }

    protected $BlockTypes = array(
        '#' => array('Header'),
        '*' => array('Rule', 'List'),
        '+' => array('List'),
        '-' => array('SetextHeader', 'Table', 'Rule', 'List'),
        '0' => array('List'),
        '1' => array('List'),
        '2' => array('List'),
        '3' => array('List'),
        '4' => array('List'),
        '5' => array('List'),
        '6' => array('List'),
        '7' => array('List'),
        '8' => array('List'),
        '9' => array('List'),
        ':' => array('Table'),
        '<' => array('Comment', 'Markup'),
        '=' => array('SetextHeader'),
        '>' => array('Quote'),
        '[' => array('Reference'),
        '_' => array('Rule'),
        '`' => array('FencedCode'),
        '|' => array('Table'),
        '~' => array('FencedCode'),
    );

    protected $unmarkedBlockTypes = array('Code');

    protected $InlineTypes = array(
        '"' => array('SpecialCharacter'),
        '!' => array('Image'),
        '&' => array('SpecialCharacter'),
        '*' => array('Emphasis'),
        ':' => array('Url'),
        '<' => array('UrlTag', 'EmailTag', 'Markup', 'SpecialCharacter'),
        '>' => array('SpecialCharacter'),
        '[' => array('Link'),
        '_' => array('Emphasis'),
        '`' => array('Code'),
        '~' => array('Strikethrough'),
        '\\' => array('EscapeSequence'),
    );

    protected $inlineMarkerList = '!"*_&[:<>`~\\';

    protected $DefinitionData = array();

    protected $specialCharacters = array(
        '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!', '|',
    );

    protected $StrongRegex = array(
        '*' => '/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*[*])+?)[*]{2}(?![*])/s',
        '_' => '/^__((?:\\\\_|[^_]|_[^_]*_)+?)__(?!_)/us',
    );

    protected $EmRegex = array(
        '*' => '/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
        '_' => '/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us',
    );

    protected $regexHtmlAttribute = '[a-zA-Z_:][\w:.-]*(?:\s*=\s*(?:[^"\'=<>`\s]+|"[^"]*"|\'[^\']*\'))?';

    protected $voidElements = array(
        'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source',
    );

    protected $textLevelElements = array(
        'a', 'br', 'bdo', 'abbr', 'blink', 'nextid', 'acronym', 'basefont',
        'b', 'em', 'big', 'cite', 'small', 'spacer', 'listing',
        'i', 'rp', 'del', 'code', 'strike', 'marquee',
        'q', 'rt', 'ins', 'font', 'strong',
        's', 'tt', 'kbd', 'mark',
        'u', 'xm', 'sub', 'nobr',
        'sup', 'ruby', 'var', 'span', 'wbr', 'time',
    );

    protected $breaksEnabled;
    protected $markupEscaped;
    protected $urlsLinked = true;

    function setBreaksEnabled($breaksEnabled)
    {
        $this->breaksEnabled = $breaksEnabled;
        return $this;
    }

    function setMarkupEscaped($markupEscaped)
    {
        $this->markupEscaped = $markupEscaped;
        return $this;
    }

    function setUrlsLinked($urlsLinked)
    {
        $this->urlsLinked = $urlsLinked;
        return $this;
    }

    function setSafeMode($safeMode)
    {
        $this->safeMode = (bool) $safeMode;
        return $this;
    }
}

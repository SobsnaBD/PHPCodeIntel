<?php

namespace PHPIntel\Context;

use PHPIntel\Context\Parser\TolerantParser;
use PHPIntel\Context\Visitor\VariableClassResolverVisitor;
use PHPIntel\Context\Lexer\LexerUtil;
use PHPIntel\Context\Lexer\Lexer;
use PHPIntel\Context\Context;

use PHPIntel\Logger\Logger;

use \Exception;

/*
* ContextBuilder
*/
class ContextBuilder
{
    public function __construct()
    {
    }

    public function buildContext($full_php_content, $current_position)
    {
        $php_content = substr($full_php_content, 0, $current_position);
        $lexer = new Lexer();
        $parser = new TolerantParser($lexer);

        try {
            // parse into statements
            $statements = $parser->parse($php_content);

        } catch (Exception $e) {
            Logger::error($e);
        }

        $tokens = $lexer->getTokens();
        $position_map = LexerUtil::buildTokenPositionMap($tokens);
        
        $context = $this->resolveContext($tokens, $position_map, $current_position, $statements);
        return $context;
    }



    public function resolveContext($tokens, $position_map, $str_position, $statements)
    {
        $token_offset = LexerUtil::findTokenOffsetByStringPosition($tokens, $position_map, $str_position);
        if ($token_offset < 2) { return null; }

        // build a string of the last three tokens
        $token_0 = LexerUtil::buildTokenDescriptionArray($tokens[$token_offset - 2]);
        $token_1 = LexerUtil::buildTokenDescriptionArray($tokens[$token_offset - 1]);
        $token_2 = LexerUtil::buildTokenDescriptionArray($tokens[$token_offset - 0]);
        // Logger::log("tokens are  0:".token_name($token_0[0])." 1:".token_name($token_1[0])." 2:".token_name($token_2[0])."");

        $context_data = array();
        switch (true) {

            // Classname::something
            case $token_0[0] == T_STRING AND $token_1[0] == T_DOUBLE_COLON AND $token_2[0] = T_STRING:
                $context_data['scope']  = 'static';
                $context_data['class']  = $token_0[1];
                $context_data['prefix'] = $token_2[1];
                break;

            // Classname::
            case $token_1[0] == T_STRING AND $token_2[0] == T_DOUBLE_COLON:
                $context_data['scope']  = 'static';
                $context_data['class']  = $token_1[1];
                $context_data['prefix'] = '';
                break;

            // $a->something
            case $token_0[0] == T_VARIABLE AND $token_1[0] == T_OBJECT_OPERATOR AND $token_2[0] = T_STRING:
                $context_data['scope']    = 'instance';
                $context_data['variable'] = $token_0[1];
                $context_data['prefix']   = $token_2[1];
                $context_data['class']    = $this->resolveClassForVariable($context_data['variable'], $str_position, $statements);
                break;

            // $a->
            case $token_1[0] == T_VARIABLE AND $token_2[0] == T_OBJECT_OPERATOR:
                $context_data['scope']    = 'instance';
                $context_data['variable'] = $token_1[1];
                $context_data['prefix']   = '';
                $context_data['class']    = $this->resolveClassForVariable($context_data['variable'], $str_position, $statements);
                break;
            
            default:
                break;
        }

        if ($context_data) { return new Context($context_data); }

        return null;
    }


    protected function resolveClassForVariable($variable, $current_position, $statements)
    {
        // the collector will visit all the nodes and collect data
        $visitor = new VariableClassResolverVisitor($variable, $current_position);
        $traverser = new \PHPParser_NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($statements);

        // get the class name
        return $visitor->getResolvedClassName();
    }

}

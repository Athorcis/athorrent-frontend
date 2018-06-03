<?php

namespace Athorrent\Cache\Twig\TokenParser;

use Athorrent\Cache\Twig\Node\CacheNode;
use Phpfastcache\Bundle\Twig\CacheExtension\TokenParser\Cache as BaseTokerParser;

class Cache extends BaseTokerParser
{
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $annotation = $this->parser->getExpressionParser()->parseExpression();

        if ($stream->nextIf(\Twig_Token::BLOCK_END_TYPE)) {
            $key = new \Twig_Node_Expression_Constant(null, $lineno);
        } else {
            $key = $this->parser->getExpressionParser()->parseExpression();
            $stream->expect(\Twig_Token::BLOCK_END_TYPE);
        }

        $body = $this->parser->subparse([$this, 'decideCacheEnd'], true);
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new CacheNode($annotation, $key, $body, $lineno, $this->getTag());
    }
}

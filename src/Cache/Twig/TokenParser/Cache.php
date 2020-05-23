<?php

namespace Athorrent\Cache\Twig\TokenParser;

use Athorrent\Cache\Twig\Node\CacheNode;
use Phpfastcache\Bundle\Twig\CacheExtension\TokenParser\Cache as BaseTokerParser;
use Twig\Node\Expression\ConstantExpression;
use Twig\Token;

class Cache extends BaseTokerParser
{
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $annotation = $this->parser->getExpressionParser()->parseExpression();

        if ($stream->nextIf(Token::BLOCK_END_TYPE)) {
            $key = new ConstantExpression(null, $lineno);
        } else {
            $key = $this->parser->getExpressionParser()->parseExpression();
            $stream->expect(Token::BLOCK_END_TYPE);
        }

        $body = $this->parser->subparse([$this, 'decideCacheEnd'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new CacheNode($annotation, $key, $body, $lineno, $this->getTag());
    }
}

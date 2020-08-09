<?php

namespace Athorrent\Cache\Twig\Node;

use Athorrent\Cache\Twig\CacheExtension;
use Twig\Compiler;
use Twig\Environment;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;
use function version_compare;

class CacheNode extends Node
{
    private static $cacheCount = 1;

    public function __construct(AbstractExpression $annotation, AbstractExpression $keyInfo, Node $body, $lineno, $tag = null)
    {
        parent::__construct([
            'key_info' => $keyInfo,
            'body' => $body,
            'annotation' => $annotation
        ], [], $lineno, $tag);
    }

    /**
     * {@inheritDoc}
     */
    public function compile(Compiler $compiler)
    {
        $i = self::$cacheCount++;

        if (version_compare(Environment::VERSION, '1.26.0', '>=')) {
            $extension = CacheExtension::class;
        } else {
            $extension = 'phpfastcache_cache';
        }

        $compiler
            ->addDebugInfo($this)
            ->write("\$phpfastcacheCacheStrategy".$i." = \$this->env->getExtension('{$extension}')->getCacheStrategy();\n")
            ->write("\$phpfastcacheKey".$i." = \$phpfastcacheCacheStrategy".$i."->generateKey(")
            ->subcompile($this->getNode('annotation'))
            ->raw(", ")
            ->subcompile($this->getNode('key_info'))
            ->write(");\n")
            ->write("\$phpfastcacheCacheBody".$i." = \$phpfastcacheCacheStrategy".$i."->fetchBlock(\$phpfastcacheKey".$i.", \$this->getSourceContext());\n")
            ->write("if (\$phpfastcacheCacheBody".$i." === false) {\n")
            ->indent()
            ->write("\\ob_start();\n")
            ->write("\$compileMc = \\microtime(true);\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->outdent()
            ->write("\n")
            ->write("\$phpfastcacheCacheBody".$i." = \\ob_get_clean();\n")
            ->write("\$phpfastcacheCacheStrategy".$i."->saveBlock(\$phpfastcacheKey".$i.", \$phpfastcacheCacheBody".$i.", \\microtime(true) - \$compileMc, \$this->getSourceContext());\n")
            ->outdent()
            ->write("}\n")
            ->write("echo \$phpfastcacheCacheBody".$i.";\n")
        ;
    }
}

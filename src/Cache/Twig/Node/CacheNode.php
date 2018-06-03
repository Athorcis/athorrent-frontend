<?php

namespace Athorrent\Cache\Twig\Node;

use Athorrent\Cache\Twig\CacheExtension;

class CacheNode extends \Twig_Node
{
    private static $cacheCount = 1;

    public function __construct(\Twig_Node_Expression $annotation, \Twig_Node_Expression $keyInfo, \Twig_Node $body, $lineno, $tag = null)
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
    public function compile(\Twig_Compiler $compiler)
    {
        $i = self::$cacheCount++;

        if (\version_compare(\Twig_Environment::VERSION, '1.26.0', '>=')) {
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

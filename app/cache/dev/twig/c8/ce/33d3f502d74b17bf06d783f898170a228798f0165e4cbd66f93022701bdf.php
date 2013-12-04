<?php

/* AcmeChessBundle::layout.html.twig */
class __TwigTemplate_c8ce33d3f502d74b17bf06d783f898170a228798f0165e4cbd66f93022701bdf extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
            'title' => array($this, 'block_title'),
            'body' => array($this, 'block_body'),
            'content' => array($this, 'block_content'),
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<!doctype html>
<html lang=\"en\" ng-app>
<head>
    <meta charset=\"utf-8\">
    <link rel=\"icon\" href=\"";
        // line 5
        echo twig_escape_filter($this->env, $this->env->getExtension('assets')->getAssetUrl("bundles/chess/favicon.ico"), "html", null, true);
        echo "\" />
    <script src=\"";
        // line 6
        echo twig_escape_filter($this->env, $this->env->getExtension('assets')->getAssetUrl("bundles/chess/angular.min.js"), "html", null, true);
        echo "\"></script>
    <title>Chess - ";
        // line 7
        $this->displayBlock('title', $context, $blocks);
        echo "</title>
</head>
    <body>
    ";
        // line 10
        $this->displayBlock('body', $context, $blocks);
        // line 15
        echo "    </body>
</html>";
    }

    // line 7
    public function block_title($context, array $blocks = array())
    {
        echo "default title";
    }

    // line 10
    public function block_body($context, array $blocks = array())
    {
        // line 11
        echo "        <div class=\"block\">
            ";
        // line 12
        $this->displayBlock('content', $context, $blocks);
        // line 13
        echo "        </div>
    ";
    }

    // line 12
    public function block_content($context, array $blocks = array())
    {
    }

    public function getTemplateName()
    {
        return "AcmeChessBundle::layout.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  68 => 12,  63 => 13,  61 => 12,  58 => 11,  55 => 10,  49 => 7,  44 => 15,  42 => 10,  36 => 7,  32 => 6,  28 => 5,  22 => 1,  45 => 8,  41 => 7,  38 => 6,  35 => 5,  29 => 3,);
    }
}

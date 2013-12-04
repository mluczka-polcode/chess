<?php

/* AcmeChessBundle:Default:index.html.twig */
class __TwigTemplate_d865ad93bc7f53b673dfa189e7a2ba3129d93616cb655c7cd8944fb86d4c049a extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = $this->env->loadTemplate("AcmeChessBundle::layout.html.twig");

        $this->blocks = array(
            'title' => array($this, 'block_title'),
            'content' => array($this, 'block_content'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "AcmeChessBundle::layout.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_title($context, array $blocks = array())
    {
        echo "home page";
    }

    // line 5
    public function block_content($context, array $blocks = array())
    {
        // line 6
        echo "    <h1>Chess - home page</h1>
    <a href=\"";
        // line 7
        echo $this->env->getExtension('routing')->getPath("chess_createTable", array("color" => "white"));
        echo "\">create new table (white)</a>
    <br>
    <a href=\"";
        // line 9
        echo $this->env->getExtension('routing')->getPath("chess_createTable", array("color" => "black"));
        echo "\">create new table (black)</a>
";
    }

    public function getTemplateName()
    {
        return "AcmeChessBundle:Default:index.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  46 => 9,  41 => 7,  38 => 6,  35 => 5,  29 => 3,);
    }
}

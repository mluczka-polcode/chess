<?php

/* AcmeChessBundle:Default:table.html.twig */
class __TwigTemplate_b35790a4ebf8357132001e1c8e1d99538491b3d6a339627600f71e2b4331ab0d extends Twig_Template
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
        echo "table";
    }

    // line 5
    public function block_content($context, array $blocks = array())
    {
        // line 6
        echo "    <h1>Chess - table</h1>
    <p>tableId: ";
        // line 7
        echo twig_escape_filter($this->env, (isset($context["tableId"]) ? $context["tableId"] : $this->getContext($context, "tableId")), "html", null, true);
        echo "</p>
    <p>your color: ";
        // line 8
        echo twig_escape_filter($this->env, (isset($context["color"]) ? $context["color"] : $this->getContext($context, "color")), "html", null, true);
        echo "</p>
    <p>game position:<br><pre>";
        // line 9
        echo twig_escape_filter($this->env, (isset($context["position"]) ? $context["position"] : $this->getContext($context, "position")), "html", null, true);
        echo "</pre></p>
    <p>game log:<br>";
        // line 10
        echo nl2br(twig_escape_filter($this->env, (isset($context["log"]) ? $context["log"] : $this->getContext($context, "log")), "html", null, true));
        echo "</p>
    <br>
    <a href=\"";
        // line 12
        echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("chess_startGame", array("tableId" => (isset($context["tableId"]) ? $context["tableId"] : $this->getContext($context, "tableId")))), "html", null, true);
        echo "\">start game</a>
";
    }

    public function getTemplateName()
    {
        return "AcmeChessBundle:Default:table.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  58 => 12,  53 => 10,  49 => 9,  45 => 8,  41 => 7,  38 => 6,  35 => 5,  29 => 3,);
    }
}

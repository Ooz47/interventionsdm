<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* modules/contrib/addtoany/templates/addtoany-standard.html.twig */
class __TwigTemplate_51523d14118046667814628135c6e6bc extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension(SandboxExtension::class);
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 20
        if ((($context["button_setting"] ?? null) != "none")) {
            // line 21
            $context["universal_button"] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros, $blocks) {
                // line 22
                yield "<a class=\"a2a_dd addtoany_share\" href=\"https://www.addtoany.com/share#url=";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::urlencode($this->sandbox->ensureToStringAllowed(($context["link_url"] ?? null), 22, $this->source)), "html", null, true);
                yield "&amp;title=";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::urlencode($this->sandbox->ensureToStringAllowed(($context["link_title"] ?? null), 22, $this->source)), "html", null, true);
                yield "\">";
                // line 23
                if (($context["button_image"] ?? null)) {
                    // line 24
                    yield "<img src=\"";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["button_image"] ?? null), 24, $this->source), "html", null, true);
                    yield "\" alt=\"";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Share"));
                    yield "\">";
                }
                // line 26
                yield "</a>";
                return; yield '';
            })())) ? '' : new Markup($tmp, $this->env->getCharset());
        }
        // line 30
        yield "<span class=\"a2a_kit a2a_kit_size_";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["buttons_size"] ?? null), 30, $this->source), "html", null, true);
        yield " addtoany_list\" data-a2a-url=\"";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["link_url"] ?? null), 30, $this->source), "html", null, true);
        yield "\" data-a2a-title=\"";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["link_title"] ?? null), 30, $this->source), "html", null, true);
        yield "\">";
        // line 31
        if ((($context["universal_button_placement"] ?? null) == "before")) {
            // line 32
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["universal_button"] ?? null), 32, $this->source), "html", null, true);
        }
        // line 35
        if (($context["addtoany_html"] ?? null)) {
            // line 36
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->sandbox->ensureToStringAllowed(($context["addtoany_html"] ?? null), 36, $this->source));
        }
        // line 39
        if ((($context["universal_button_placement"] ?? null) == "after")) {
            // line 40
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["universal_button"] ?? null), 40, $this->source), "html", null, true);
        }
        // line 42
        yield "</span>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["button_setting", "link_url", "link_title", "button_image", "buttons_size", "universal_button_placement", "addtoany_html"]);        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "modules/contrib/addtoany/templates/addtoany-standard.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  87 => 42,  84 => 40,  82 => 39,  79 => 36,  77 => 35,  74 => 32,  72 => 31,  64 => 30,  59 => 26,  52 => 24,  50 => 23,  44 => 22,  42 => 21,  40 => 20,);
    }

    public function getSourceContext()
    {
        return new Source("", "modules/contrib/addtoany/templates/addtoany-standard.html.twig", "C:\\laragon\\www\\interventions-democratiques.fr.d11\\web\\modules\\contrib\\addtoany\\templates\\addtoany-standard.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("if" => 20, "set" => 21);
        static $filters = array("escape" => 22, "url_encode" => 22, "t" => 24, "raw" => 36);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['if', 'set'],
                ['escape', 'url_encode', 't', 'raw'],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}

<?php

namespace Edge\Twig\Validator;

use Zend\Validator\AbstractValidator;
use Twig_Environment;
use Twig_Error;
use Twig_Error_Syntax;
use Twig_Sandbox_SecurityError;

class Twig extends AbstractValidator
{
    const SYNTAX_ERROR        = 'syntaxError';
    const SECURITY_ERROR      = 'securityError';
    const UNKNOWN_PARSE_ERROR = 'unknownParseError';

    protected $messageTemplates = array(
        self::SYNTAX_ERROR        => "A syntax error occured, %value%",
        self::SECURITY_ERROR      => "A security error occured, %value%",
        self::UNKNOWN_PARSE_ERROR => "An unknown error occured trying to parse the template"
    );

    /**
     * Twig Environent for testing string
     *
     * @var Twig_Environment
     */
    protected $twig = null;

    public function setTwig(Twig_Environment $twig)
    {
        $this->twig = $twig;
        return $this;
    }

    /**
     * Returns the provided or an automatically composed Twig environment
     *
     * @return Twig_Environment
     */
    public function getTwig()
    {
        if (null === $this->twig) {
            $this->twig = new Twig_Environment(new Twig_Loader_String());
        }
        return $this->twig;
    }

    public function isValid($value)
    {
        try {
            $this->twig->render($value);
        } catch (Twig_Error_Syntax $e) {
            $this->error(self::SYNTAX_ERROR, $e->getRawMessage());
            return false;
        } catch (Twig_Sandbox_SecurityError $e) {
            $this->error(self::SECURITY_ERROR, $e->getRawMessage());
            return false;
        } catch (Twig_Error $e) {
            $this->error(self::UNKNOWN_PARSE_ERROR);
            return false;
        }

        return true;
    }
}
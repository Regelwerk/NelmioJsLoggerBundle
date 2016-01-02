<?php

namespace Nelmio\JsLoggerBundle;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TwigExtension extends \Twig_Extension
{
    private $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('nelmio_js_error_logger', array($this, 'initErrorLogger'), array('is_safe' => array('html', 'js'))),
            new \Twig_SimpleFunction('nelmio_js_logger', array($this, 'initLogger'), array('is_safe' => array('html', 'js'))),
        );
    }

    public function initErrorLogger($level = 'error', $includeScriptTag = true)
    {
        $url = addslashes($this->router->generate('nelmio_js_logger_log'));
        $logFunction = $this->initLogger('log', false);

        $js = <<<JS
(function () {
    var oldErrorHandler = window.onerror,
        addEventListener,
        removeEventListener;

    var wrap = function (func) {
        if (!func._wrapped) {
            func._wrapped = function () {
                try{
                    func.apply(this, arguments);
                } catch(e) {
                    send(e.message, 'see stack trace', 0, 0, e);
                    throw e;
                }
            };
        }
        return func._wrapped;
    };
                
    $logFunction
    
    var send = function(errorMsg, file, line, column, errorObject) {
        var context = {},
            f,
            stack = [];

        context['file'] = file;
        context['line'] = line;

        if (column != null && column > 0) {
            context['column'] = column;
        }

        if (errorObject != null) {
            context['stack']  = JSON.stringify(errorObject.stack);
        } else if (arguments.callee.caller != null) {
            try {
                f = arguments.callee.caller;
                while (f) {
                    stack.push(f.name);
                    f = f.caller;
                }
            } catch (e) {} // catch strict mode error
            context['stack']  = JSON.stringify(stack);
        } else {
            context['stack']  = '';
        }

        context['browser'] = navigator.userAgent;
        context['page'] = document.location.href;

        log('$level', errorMsg, context);
    };

    window.onerror = function(errorMsg, file, line, column, errorObject){
        if (oldErrorHandler) {
            oldErrorHandler(errorMsg, file, line);
        }
        send(errorMsg, file, line, column, errorObject);
    };

    if (window.EventTarget) {
        addEventListener = window.EventTarget.prototype.addEventListener;
        window.EventTarget.prototype.addEventListener = function (event, callback, bubble) {
            addEventListener.call(this, event, wrap(callback), bubble);
        };

        removeEventListener = window.EventTarget.prototype.removeEventListener;
        window.EventTarget.prototype.removeEventListener = function (event, callback, bubble) {
             removeEventListener.call(this, event, callback._wrapped || callback, bubble);
         };
    }
})();
JS;
///        $js = preg_replace('{\n *}', '', $js);

        if ($includeScriptTag) {
            $js = "<script>$js</script>";
        }

        return $js;
    }

    public function initLogger($function = 'log', $includeScriptTag = true)
    {
        $url = addslashes($this->router->generate('nelmio_js_logger_log'));

        $js = <<<JS
var $function = function(level, message, contextData) {
    var key,
        context = '',
        customContext = window.nelmio_js_logger_custom_context,
        e = encodeURIComponent;

    if (contextData) {
        for (key in contextData) {
            context += '&context[' + e(key) + ']=' + e(contextData[key]);
        }
    }
    if ('object' === typeof customContext) {
        for (key in customContext) {
            context += '&context[' + e(key) + ']=' + e(customContext[key]);
        }
    }
    (new Image()).src = '$url?msg=' + e(message) + '&level=' + e(level) + context;
};
JS;

//        $js = preg_replace('{\n *}', '', $js);

        if ($includeScriptTag) {
            $js = "<script>$js</script>";
        }

        return $js;
    }

    public function getName()
    {
        return 'nelmio_js_logger';
    }
}

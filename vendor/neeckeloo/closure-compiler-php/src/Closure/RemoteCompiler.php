<?php
/**
 * ClosureCompilerPHP
 *
 * @link      http://github.com/neeckeloo/ClosureCompilerPHP
 * @copyright Copyright (c) 2012 Nicolas Eeckeloo
 */
namespace Closure;

use Closure\Compiler\Response as CompilerResponse;
use Closure\Compiler\Response\Error as CompilerResponseError;

class RemoteCompiler extends AbstractCompiler
{
    /**
     * @var string 
     */
    protected $url = 'http://closure-compiler.appspot.com/compile';

    /**
     * @var integer
     */
    protected $port = 80;

    /**
     * @var string
     */
    protected $method = \Zend\Http\Request::METHOD_POST;

    /**
     * @var \Zend\Http\Client
     */
    protected $requestHandler;

    /**
     * Sets request handler
     *
     * @param \Zend\Http\Client $handler
     * @return RemoteCompiler
     */
    public function setRequestHandler($handler)
    {
        $this->requestHandler = $handler;

        $this->requestHandler->setUri($this->url)
            ->setMethod($this->method);

        $this->requestHandler->getUri()->setPort($this->port);

        return $this;
    }

    /**
     * Returns request handler
     *
     * @return \Zend\Http\Client
     */
    public function getRequestHandler()
    {
        if (!isset($this->requestHandler)) {
            $this->setRequestHandler(new \Zend\Http\Client());
        }

        return $this->requestHandler;
    }

    /**
     * Parse response xml
     *
     * @param \SimpleXMLElement $xml
     * @return array
     */
    protected function parseXml($xml)
    {
        $data = array();
        
        foreach ($xml->children() as $name => $child) {
            if (count($child->children()) > 0) {
                $value = $this->parseXml($child);
            } else {
                $value = (string) $child;
            }

            $node = array(
                'tag'   => $name,
                'value' => $value
            );

            foreach ($child->attributes() as $name => $value) {
                $node['attributes'][$name] = (string) $value[0];
            }
            
            $data[] = $node;
        }

        return $data;
    }

    /**
     * Build response object from compiler response data
     *
     * @param array $data
     * @return CompilerResponse
     */
    protected function buildResponse($data)
    {
        $response = $this->getCompilerResponse();

        foreach ($data as $item) {
            if (!isset($item['tag']) && !isset($item['value'])) {
                continue;
            }

            if (isset($item['tag']) && ($item['tag'] == 'errors' || $item['tag'] == 'warnings')) {
                foreach ($item['value'] as $error) {
                    $attributes = $error['attributes'];
                    
                    $error = new CompilerResponseError($error['value'], array(
                        'type' => $attributes['type'],
                        'file' => $attributes['file'],
                        'line' => $attributes['lineno'],
                        'char' => $attributes['charno'],
                        'code' => $attributes['line'],
                    ));

                    if ($item['tag'] == 'errors') {
                        $response->addError($error);
                    } else {
                        $response->addWarning($error);
                    }
                }
            } elseif (is_array($item['value'])) {
                $this->buildResponse($item['value']);
            } else {
                $method = 'set' . ucfirst($item['tag']);
                if (method_exists($response, $method)) {
                    call_user_func_array(array($response, $method), array($item['value']));
                }
            }
        }

        return $response;
    }

    /**
     * Parse and encode data
     *
     * @param array $params
     * @return string
     */
    protected function encodeData($params)
    {
        $data = array();
        foreach ($params as $key => $value) {
            $key = preg_replace('/_[0-9]$/', '', $key);
            $data[] = $key . '=' . urlencode($value);
        }

        return implode('&', $data);
    }

    /**
     * Compile Javascript code
     * 
     * @return CompilerResponse
     */
    public function compile()
    {
        $requestHandler = $this->getRequestHandler();

        $encodedData = $this->encodeData($this->getParams());
        $requestHandler->setRawBody($encodedData);

        $response = $requestHandler->send();
        $xml = new \SimpleXMLElement($response->getContent());
        $data = $this->parseXml($xml);

        return $this->buildResponse($data);
    }
}
<?php
class Router {
    private $routes = [];
    private $params = [];
    
    public function add($route, $params = []) {
        $route = preg_replace('/\//', '\\/', $route);
        $route = preg_replace('/\{([a-z]+)\}/', '(?P<\1>[a-z0-9-]+)', $route);
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);
        $route = '/^' . $route . '$/i';
        
        $this->routes[$route] = $params;
    }
    
    public function dispatch($url) {
        // SÊNIOR: Não tentamos descriptografar se a rota começar com 'api/' 
        // ou se for uma rota de sistema conhecida que não deve ser mascarada.
        if (function_exists('deobfuscateUrl') && strpos($url, 'api/') !== 0) {
            $url = deobfuscateUrl($url);
        }

        $url = $this->removeQueryStringVariables($url);
        
        if ($this->match($url)) {
            $controller = $this->params['controller'];
            $controller = $this->convertToStudlyCaps($controller);
            $controller = "{$controller}Controller";
            
            if (class_exists($controller)) {
                $controller_object = new $controller($this->params);
                
                $action = $this->params['action'];
                $action = $this->convertToCamelCase($action);
                
                if (preg_match('/action$/i', $action) == 0) {
                    $controller_object->$action();
                } else {
                    throw new \Exception("Method $action in controller $controller cannot be called directly - remove the Action suffix to call this method");
                }
            } else {
                throw new \Exception("Controller class $controller not found");
            }
        } else {
            throw new \Exception('No route matched.', 404);
        }
    }
    
    private function match($url) {
        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url, $matches)) {
                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $params[$key] = $match;
                    }
                }
                
                $this->params = $params;
                return true;
            }
        }
        return false;
    }
    
    private function convertToStudlyCaps($string) {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }
    
    private function convertToCamelCase($string) {
        return lcfirst($this->convertToStudlyCaps($string));
    }
    
    private function removeQueryStringVariables($url) {
        if ($url != '') {
            $parts = explode('&', $url, 2);
            
            // Se a primeira parte contiver um '=', significa que a URL não foi limpa no index.php
            // Ajustamos para pegar apenas o valor após o '=' se for o parâmetro 'url'
            if (strpos($parts[0], '=') !== false) {
                if (strpos($parts[0], 'url=') === 0) {
                    $url = substr($parts[0], 4);
                } else {
                    $url = '';
                }
            } else {
                $url = $parts[0];
            }
        }
        return trim($url, '/');
    }
        
    public function getRoutes() {
        return $this->routes;
    }
    
    public function getParams() {
        return $this->params;
    }
}
?>
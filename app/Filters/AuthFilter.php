<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class AuthFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will stop and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments Arguments are role names or IDs passed from routes config.
     *                                    Example: ['auth:Administrator Sistem,Staf Tata Usaha']
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = Services::session();
        helper('auth'); // Load our auth helper for hasRole()

        if (!is_logged_in()) {
            // Store the attempted URL in session to redirect back after login, if desired
            // $session->set('redirect_url', current_url());
            return redirect()->to('/login')->with('error', 'You must be logged in to access this page.');
        }

        // If arguments are provided, check for role-based access
        if (!empty($arguments)) {
            $allowedRoles = $arguments; // $arguments already an array of roles from route definition

            if (!hasRole($allowedRoles)) {
                // User is logged in but does not have the required role(s)
                // Redirect to a generic unauthorized page or a specific controller action
                // For now, we'll use a route that should be set up to show the unauthorized view.
                // This requires a route like: $routes->get('unauthorized', 'SomeController::unauthorized');
                // Or we can throw a specific exception that the Exception handler can catch.
                // For simplicity with current setup, we can redirect to a BaseController method if possible,
                // but filters typically return a Response object or null.
                // Throwing an exception might be cleaner if we have a handler.
                // For now, let's prepare for a redirect to a named route '/unauthorized'
                // which we will need to define.
                // Or, more directly, call a controller method if filter can return string view.
                // However, best practice is to return a Response object.
                // Let's try redirecting to a route that will be handled by a controller method to show the page.
                // We'll need to add a route for this.
                // For now, let's use a simple redirect and flash message.
                // The proper way would be `return service('response')->setStatusCode(403)->setBody(view('errors/unauthorized'));`
                // but that bypasses the layout.
                // A redirect to a dedicated controller/method for 403 is cleaner.
                // Let's assume we have an ErrorController::show403()
                 return redirect()->to(site_url('unauthorized-access'));
            }
        }
        // If no arguments, just being logged in is enough (current behavior for /admin general)
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}

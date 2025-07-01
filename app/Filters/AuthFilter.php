<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

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
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('is_logged_in')) {
            // Store the attempted URL in session to redirect back after login
            // $session->set('redirect_url', current_url());
            return redirect()->to('/login')->with('error', 'You must be logged in to access this page.');
        }

        // Optional: Role-based access control can be added here if needed for specific filters
        // For example, if $arguments = ['admin', 'editor']
        // if (!empty($arguments) && !in_array($session->get('role_name'), $arguments)) { // Assuming role_name is in session
        //     return redirect()->to('/unauthorized')->with('error', 'You do not have permission to access this page.');
        // }
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

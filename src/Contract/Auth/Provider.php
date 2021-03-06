<?php
/**
 * 认证服务提供者接口
 */

namespace Zyh\ApiGateway\Contract\Auth;

use Illuminate\Http\Request;
use Zyh\ApiGateway\Routing\Route;

interface Provider
{
    /**
     * 请求身份验证并且返回身份验证用户实例
     *
     * @param \Illuminate\Http\Request $request
     * @param \Zyh\ApiGateway\Routing\Route $route
     *
     * @return mixed
     */
    public function authenticate(Request $request, Route $route);
}

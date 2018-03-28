<?php
/**
 * 功能：
 * 操作: 唐强
 * 日期: 3/28
 * 时间: 4:49
 */

namespace Common;

require_once(dirname(__FILE__) . "/../vendor/workerman/gatewayclient/Gateway.php");
use GatewayClient\Gateway;


class GatewayClient
{
    //构造函数，设置GatewayWorker服务的Register服务ip和端口
    public function _construct()
    {
        Gateway::$registerAddress = '127.0.0.1:1188';
    }

    /**
     * 向所有客户端连接(或者 client_id_array 指定的客户端连接)广播消息
     * @param $message
     * @param null $client_id_array
     * @param null $exclude_client_id
     * @param bool $raw
     */
    public function sendToAll($message, $client_id_array = null, $exclude_client_id = null, $raw = false)
    {
        Gateway::sendToAll($message,$client_id_array,$exclude_client_id,$raw);
    }

    /**
     * 向某个客户端连接发消息
     * @param $client_id
     * @param $message
     * @return bool
     */
    public function sendToClient($client_id,$message)
    {
        $result = Gateway::sendToClient($client_id,$message);
        return $result;
    }

    /**
     * 判断某个uid是否在线
     * @param $uid
     * @return int
     */
    public function isUidOnline($uid)
    {
        $result = Gateway::isUidOnline($uid);
        return $result;
    }

    /**判断某个客户端连接是否在线
     * @param $client_id
     * @return int
     */
    public function isOnline($client_id)
    {
        $result = Gateway::isOnline($client_id);
        return $result;
    }

    /**获取所有在线用户的连接信息
     * @param null $group
     * @return array
     */
    public function getAllClientInfo($group = null)
    {
        $result = Gateway::getAllClientInfo($group);
        return $result;
    }

    /**
     * 获取所有在线用户的session，client_id为 key
     * @param null $group
     * @return array
     */
    public function getAllClientSessions($group = null)
    {
        $result = Gateway::getAllClientSessions($group);
        return $result;
    }

    /**
     * 获取某个组的连接信息
     * @param $group
     * @return array
     */
    public function getClientInfoByGroup($group)
    {
        $result = Gateway::getClientInfoByGroup($group);
        return $result;
    }

    /**
     * 获取某个组的连接信息
     * @param $group
     * @return array
     */
    public function getClientSessionsByGroup($group)
    {
        $result = Gateway::getClientSessionsByGroup($group);
        return $result;
    }

    /**
     * 获取所有连接数
     * @return int
     */
    public function getAllClientCount()
    {
        $result = Gateway::getAllClientCount();
        return $result;
    }

    /**
     * 获取某个组的在线连接数
     * @param string $group
     * @return int
     */
    public function getClientCountByGroup($group = '')
    {
        $result = Gateway::getClientCountByGroup($group);
        return $result;
    }

    /**
     * 获取与 uid 绑定的 client_id 列表
     * @param $uid
     * @return array
     */
    public function getClientIdByUid($uid)
    {
        $result = Gateway::getClientIdByUid($uid);
        return $result;
    }

    /**
     * 关闭某个客户端
     * @param $client_id
     * @return bool
     */
    public function closeClient($client_id)
    {
        $result = Gateway::closeClient($client_id);
        return $result;
    }

    /**
     * 将 client_id 与 uid 绑定
     * @param $client_id
     * @param $uid
     * @return bool
     */
    public function bindUid($client_id, $uid)
    {
        $result = Gateway::bindUid($client_id, $uid);
        return $result;
    }

    /**
     * 将 client_id 与 uid 解除绑定
     * @param $client_id
     * @param $uid
     * @return bool
     */
    public function unbindUid($client_id, $uid)
    {
        $result = Gateway::unbindUid($client_id, $uid);
        return $result;
    }

    /**
     * 将 client_id 加入组
     * @param $client_id
     * @param $group
     * @return bool
     */
    public function joinGroup($client_id, $group)
    {
        $result = Gateway::joinGroup($client_id, $group);
        return $result;
    }

    /**
     * 将 client_id 离开组
     * @param $client_id
     * @param $group
     * @return bool
     */
    public function leaveGroup($client_id, $group)
    {
        $result = Gateway::leaveGroup($client_id, $group);
        return $result;
    }

    /**
     * 向所有 uid 发送
     * @param $uid
     * @param $message
     */
    public function sendToUid($uid, $message)
    {
        Gateway::sendToUid($uid,$message);
    }

    /**
     * 向 group 发送
     * @param $group
     * @param $message
     * @param null $exclude_client_id
     * @param bool $raw
     */
    public function sendToGroup($group, $message, $exclude_client_id = null, $raw = false)
    {
        Gateway::sendToGroup($group, $message, $exclude_client_id, $raw);
    }

    /**
     * 设置 session，原session值会被覆盖
     * @param $client_id
     * @param array $session
     * @return bool
     */
    public function setSession($client_id, array $session)
    {
        $result = Gateway::setSession($client_id,$session);
        return $result;
    }

    /**
     * 更新 session，实际上是与老的session合并
     * @param $client_id
     * @param array $session
     * @return bool
     */
    public function updateSession($client_id, array $session)
    {
        $result = Gateway::updateSession($client_id,$session);
        return $result;
    }

    /**
     * 获取某个client_id的session
     * @param $client_id
     * @return mixed
     */
    public function getSession($client_id)
    {
        $result = Gateway::getSession($client_id);
        return $result;
    }
}
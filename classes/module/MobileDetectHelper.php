<?php

declare (strict_types=1);
/**
 * Copyright (C) 2025-2025 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2025-2025 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
namespace Thirtybees\Core\Module;

use Context;
use Db;
use Db_Query;
use Module;
use Presta_Shop_Exception;
use Thirtybees\Core\Dependency_Injection\Service_Locator;
use Thirtybees\Core\Error\Error_Utils;
use Throwable;
class Mobile_Detect_Helper_Core
{
    private static ?bool $is_tablet = null;
    private static ?bool $is_mobile = null;
    private static ?string $user_agent = null;
    public function is_tablet(): bool
    {
        static::detect();
        return (bool) static::$is_tablet;
    }
    public function is_mobile(): bool
    {
        static::detect();
        return (bool) static::$is_mobile;
    }
    public function get_user_agent(): string
    {
        static::detect();
        return (string) static::$user_agent;
    }
    protected static function detect(): void
    {
        if (is_null(static::$is_tablet)) {
            static::$is_mobile = false;
            static::$is_tablet = false;
            static::$user_agent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
            try {
                foreach (static::get_modules_responses() as $response) {
                    if (isset($response['isTablet']) && $response['isTablet']) {
                        static::$is_tablet = true;
                    }
                    if (isset($response['isMobile']) && $response['isMobile']) {
                        static::$is_mobile = true;
                    }
                    if (isset($response['userAgent'])) {
                        static::$user_agent = (string) $response['userAgent'];
                    }
                }
            } catch (Throwable $e) {
                $error_handler = Service_Locator::get_instance()->get_error_handler();
                $error_handler->log_fatal_error(Error_Utils::describe_exception($e));
            }
        }
    }
    /**
     * Executes hook 'actionDetectMobile' for all installed modules
     *
     * Normally, we would use Hook::getResponses() for this. Unfortunately, that method
     * depends on device type information, which would cause infinite recursion. So we have to
     * call the hook handlers manually in this specific case
     *
     *
     * @throws PrestaShopException
     */
    protected static function get_modules_responses(): array
    {
        $responses = [];
        $sql = (new Db_Query())->select('DISTINCT m.name')->from('module', 'm')->inner_join('module_shop', 'ms', 'ms.`id_module` = m.`id_module`')->inner_join('hook_module', 'hm', 'hm.`id_module` = m.`id_module` AND hm.`id_shop` = ms.`id_shop`')->inner_join('hook', 'h', 'hm.`id_hook` = h.`id_hook`')->where('ms.id_shop = ' . (int) Context::get_context()->shop->id)->where('m.active')->where('ms.enable_device > 0')->where('h.name = "actionDetectMobile"')->order_by('hm.position');
        $conn = Db::get_instance();
        foreach ($conn->get_array($sql) as $row) {
            $module_name = $row['name'];
            $module_instance = Module::get_instance_by_name($module_name);
            if ($module_instance && is_callable([$module_instance, 'hookActionDetectMobile'])) {
                $responses[$module_name] = $module_instance->hook_action_detect_mobile();
            }
        }
        return $responses;
    }
}
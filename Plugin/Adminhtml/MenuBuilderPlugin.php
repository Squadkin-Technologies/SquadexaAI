<?php
/**
 * Copyright © Squadkin Technologies Pvt. Ltd. All rights reserved.
 */

namespace Squadkin\SquadexaAI\Plugin\Adminhtml;

use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Menu\Builder;

/**
 * Plugin to merge SquadexaAI menu items into Squadkin_Base menu when Base module is present
 */
class MenuBuilderPlugin
{
    /**
     * After plugin for getResult to merge menus when Base module is present
     *
     * @param Builder $subject
     * @param Menu $menu
     * @return Menu
     */
    public function afterGetResult(Builder $subject, Menu $menu)
    {
        try {
            $baseMenuExists = false;
            
            // Check if Squadkin::top_level exists (from Base module)
            try {
                $baseMenuItem = $menu->get('Squadkin::top_level');
                $baseMenuExists = true;
            } catch (\OutOfBoundsException $e) {
                // Base menu doesn't exist - nothing to do, items stay under SquadexaAI::root
                return $menu;
            }
            
            // Base exists - move SquadexaAI main menu from root to Base
            if ($baseMenuExists && $baseMenuItem) {
                try {
                    $squadexaRootItem = $menu->get('Squadkin_SquadexaAI::root');
                    $mainMenuItem = $menu->get('Squadkin_SquadexaAI::squadexaiproductcreator_main_menu');
                    
                    if ($squadexaRootItem && $mainMenuItem && $baseMenuItem) {
                        // Remove main menu from SquadexaAI root's children
                        $rootChildren = $squadexaRootItem->getChildren();
                        if ($rootChildren) {
                            $rootChildren->remove($mainMenuItem->getId());
                        }
                        
                        // Add it to Base menu's children
                        $baseChildren = $baseMenuItem->getChildren();
                        if ($baseChildren) {
                            $baseChildren->add($mainMenuItem);
                        }
                        
                        // Remove the now-empty SquadexaAI root
                        if (!$squadexaRootItem->hasChildren()) {
                            $menu->remove('Squadkin_SquadexaAI::root');
                        }
                    }
                } catch (\OutOfBoundsException $e) {
                    // Items don't exist, nothing to move
                }
            }
        } catch (\Exception $e) {
            // Silently fail if menu items don't exist
            // This ensures the plugin doesn't break the menu if structure changes
        }

        return $menu;
    }
}

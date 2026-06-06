<?php
/**
 * Copyright © 2024 Squadkin. All rights reserved.
 */
declare(strict_types=1);

namespace Squadkin\SquadexaAI\Model;

/**
 * Provides SVG icon definitions for dashboard
 */
class DashboardIconsProvider
{
    /**
     * Get SVG icons as JSON for JavaScript consumption
     *
     * @return string JSON encoded SVG icons
     */
    public function getIconsJson(): string
    {
        $icons = $this->getIcons();
        return json_encode($icons);
    }

    /**
     * Get SVG icon definitions
     *
     * @return array SVG icon definitions
     */
    public function getIcons(): array
    {
        return [
            'wallet' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
                . 'stroke-width="2"><path d="M21 12a1 1 0 0 0-1-1h-7a4 4 0 0 0-4 4v1a2 2 0 '
                . '0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6"/><circle cx="17.5" cy="17.5" '
                . 'r="1.5"/></svg>',
            'plus' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
                . 'stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" '
                . 'x2="19" y2="12"/></svg>',
            'chart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
                . 'stroke-width="2"><polyline points="12 3 20 7.5 20 16.5 12 21 4 16.5 4 7.5 '
                . '12 3"/><polyline points="12 12 20 7.5"/><polyline points="12 12 12 21"/>'
                . '<polyline points="12 12 4 7.5"/></svg>',
            'check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
                . 'stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>',
            'key' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
                . 'stroke-width="2"><path d="M21 2l-9 4m0 0L5 2m7 4v13m0 0l-4-4m4 4l4-4"/>'
                . '<circle cx="7.5" cy="15.5" r="1.5"/></svg>',
            'copy' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
                . 'stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>'
                . '<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>',
            'checkCircle' => '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" '
                . 'cy="12" r="10"/><path d="M16 8l-8 8m0-8l8 8" stroke="white" stroke-width="2" '
                . 'fill="none"/></svg>',
            'fire' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
                . 'stroke-width="2"><path d="M12 2c-1 2-2 4-2 7 0 2.236 1.79 4 4 4s4-1.764 4-4'
                . 'c0-3-1-5-2-7zM4 16c.667-1.333 2-2 4-2s3 .667 4 2c-1.333.667-3 1-4 1s-2.333'
                . '-.333-4-1z"/><path d="M8 20c0-1 1-2 2-2s2 1 2 2c0 1-1 2-2 2s-2-1-2-2z"/></svg>',
            'bolt' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
                . 'stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>'
                . '</svg>',
            'gauge' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
                . 'stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>',
            'doc' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" '
                . 'stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 '
                . '0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="13" x2="12" '
                . 'y2="17"/><line x1="9" y1="16" x2="15" y2="16"/></svg>',
        ];
    }
}

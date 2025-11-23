/**
 * Copyright © 2024 Squadkin. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiRegistry',
    'mage/translate'
], function ($, registry, $t) {
    'use strict';

    var pollingIntervals = {}; // Store intervals per CSV ID
    var hasRefreshed = {}; // Track if grid has been refreshed for each job
    var pollUrl = null;
    var formKey = null;

    /**
     * Start polling for a specific generated CSV record
     * 
     * @param {number} generatedCsvId
     */
    function startPolling(generatedCsvId) {
        // Stop any existing polling for this ID
        if (pollingIntervals[generatedCsvId]) {
            clearInterval(pollingIntervals[generatedCsvId]);
        }


        // Poll immediately on start
        checkJobStatus(generatedCsvId);

        // Then poll every 10 seconds
        pollingIntervals[generatedCsvId] = setInterval(function() {
            checkJobStatus(generatedCsvId);
        }, 10000); // 10 seconds
    }

    /**
     * Stop polling for a specific generated CSV record
     * 
     * @param {number} generatedCsvId
     */
    function stopPolling(generatedCsvId) {
        if (pollingIntervals[generatedCsvId]) {
            clearInterval(pollingIntervals[generatedCsvId]);
            delete pollingIntervals[generatedCsvId];
        }
    }

    /**
     * Check job status via AJAX
     * 
     * @param {number} generatedCsvId
     */
    function checkJobStatus(generatedCsvId) {
        if (!pollUrl || !formKey) {
            return;
        }

        $.ajax({
            url: pollUrl,
            type: 'POST',
            data: {
                'generatedcsv_id': generatedCsvId,
                'form_key': formKey
            },
            dataType: 'json',
            showLoader: false, // Don't show loader for background polling
            success: function(response) {
                if (response.success) {
                    // If status is completed, refresh grid and stop polling
                    if (response.status === 'completed') {
                        stopPolling(generatedCsvId);
                        
                        // Refresh grid when status becomes completed
                        // Use a small delay to ensure database is updated
                        setTimeout(function() {
                            if (!hasRefreshed[generatedCsvId]) {
                                hasRefreshed[generatedCsvId] = true;
                                refreshGrid();
                            }
                        }, 1000);
                    } else if (response.status === 'failed') {
                        stopPolling(generatedCsvId);
                        // Refresh grid to show failed status
                        setTimeout(function() {
                            refreshGrid();
                        }, 1000);
                    }
                    // Continue polling for pending/processing status
                } else {
                    // Stop polling on error only after multiple failures
                }
            },
            error: function(xhr, status, error) {
                // Don't stop polling on transient errors
                // Log error but continue polling
            }
        });
    }

    /**
     * Refresh the grid once
     */
    function refreshGrid() {
        try {
            var listingProvider = registry.get(
                'squadkin_squadexaai_generatedcsv_listing.squadkin_squadexaai_generatedcsv_listing_data_source'
            );
            
            if (listingProvider && typeof listingProvider.reload === 'function') {
                listingProvider.reload({
                    refresh: true
                });
            } else {
                // Alternative method: trigger grid refresh through UI component
                var listingComponent = registry.get('squadkin_squadexaai_generatedcsv_listing');
                if (listingComponent && listingComponent.source) {
                    listingComponent.source.reload({
                        refresh: true
                    });
                } else {
                    // Fallback: reload page after delay
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                }
            }
        } catch (e) {
            // Fallback to page reload
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        }
    }

    /**
     * Initialize polling for all pending jobs
     */
    function initializePolling() {
        function checkGridForPendingJobs() {
            try {
                // Try to get listing provider
                var listingProvider = registry.get(
                    'squadkin_squadexaai_generatedcsv_listing.squadkin_squadexaai_generatedcsv_listing_data_source'
                );
                
                if (listingProvider) {
                    // Try different ways to access rows
                    var rows = listingProvider.rows || 
                              (listingProvider.data && listingProvider.data.items) ||
                              (listingProvider.data && listingProvider.data) ||
                              [];
                    
                    if (Array.isArray(rows) && rows.length > 0) {
                        rows.forEach(function(row) {
                            var csvId = row.generatedcsv_id || row['generatedcsv_id'];
                            var jobId = row.job_id || row['job_id'];
                            var responseFile = row.response_file_name || row['response_file_name'];
                            var status = row.import_status || row['import_status'] || '';
                            
                            // Start polling if job_id exists but file is not ready
                            // Also poll if status is completed but file not yet downloaded (file might be processing)
                            if (csvId && jobId && !responseFile && 
                                (status === 'pending' || status === 'processing' || status === 'in_progress' || status === '' || status === 'completed')) {
                                // Only start polling if not already polling
                                if (!pollingIntervals[csvId]) {
                                    startPolling(parseInt(csvId));
                                }
                            }
                        });
                    }
                }
            } catch (e) {
            }
        }

        // Try immediately
        checkGridForPendingJobs();
        
        // Also try after delays to catch grid when it loads
        setTimeout(checkGridForPendingJobs, 1000);
        setTimeout(checkGridForPendingJobs, 3000);
        setTimeout(checkGridForPendingJobs, 5000);
        
        // Listen for grid reloads
        registry.get('squadkin_squadexaai_generatedcsv_listing', function(component) {
            if (component && component.source) {
                component.source.on('reloaded', function() {
                    setTimeout(checkGridForPendingJobs, 500);
                });
            }
        });
    }

    // Return function for x-magento-init
    return function (config) {
        if (config) {
            pollUrl = config.pollUrl;
            formKey = config.formKey;
        }

        // Initialize when DOM is ready
        $(document).ready(function() {
            // Wait a bit for Magento UI components to initialize
            setTimeout(initializePolling, 1000);
        });

        // Cleanup on page unload
        $(window).on('beforeunload', function() {
            Object.keys(pollingIntervals).forEach(function(csvId) {
                stopPolling(csvId);
            });
        });
    };
});

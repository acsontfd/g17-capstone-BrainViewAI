/**
 * BrainView AI Patient Manager
 * JS File to manage patient CT scan records and results
 */

document.addEventListener('DOMContentLoaded', function() {
    // UI
    const patientSearch = document.getElementById('patientSearch');
    const searchBtn = document.getElementById('searchBtn');
    const clearBtn = document.getElementById('clearBtn');
    const patientResults = document.getElementById('patientResults');
    const patientTableBody = document.getElementById('patientTableBody');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const noResults = document.getElementById('noResults');
    const patientModal = document.getElementById('patientModal');
    const closeModalBtn = document.getElementById('closeModalBtn');

    loadPatientData();

    //SEARCH
    searchBtn.addEventListener('click', function() {
        const searchTerm = patientSearch.value.trim();
        loadPatientData(searchTerm);
    });
    clearBtn.addEventListener('click', function() {
        patientSearch.value = '';
        loadPatientData();
    });
    patientSearch.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchBtn.click();
        }
    });
    closeModalBtn.addEventListener('click', function() {
        patientModal.style.display = 'none';
    });
    window.addEventListener('click', function(e) {
        if (e.target === patientModal) {
            patientModal.style.display = 'none';
        }
    });

    function loadPatientData(patientId = '') {
        loadingIndicator.style.display = 'block';
        patientResults.style.display = 'none';
        noResults.style.display = 'none';

        console.log('Loading patient data. Filter by patientId:', patientId || 'none (showing all)');
        let url = 'get-patient-data.php';
        if (patientId) {
            url += '?patientId=' + encodeURIComponent(patientId);
        }

        const options = {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        };
        fetch(url, options)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                loadingIndicator.style.display = 'none';
                //DEBUG
                console.log('Response data:', data);
                
                if (data.success && data.count > 0) {
                    renderPatientTable(data.data);
                    patientResults.style.display = 'block';
                } else if (data.success && data.count === 0) {
                    noResults.innerHTML = '<p>No patient records found. Please try a different search term or <a href="insert-test-data.php">create test data</a>.</p>';
                    noResults.style.display = 'block';
                } else {
                    //DEBUG
                    let errorMessage = 'Error loading patient data: ' + (data.error || 'Unknown error');
                    if (data.error_code === 'AUTH_REQUIRED') {
                        errorMessage = 'Your session has expired. Please <a href="login.html">login again</a>.';
                    }
                    noResults.innerHTML = '<p>' + errorMessage + '</p>';
                    if (data.debug_info) {
                        noResults.innerHTML += '<details><summary>Technical Details</summary><pre>' + 
                            JSON.stringify(data.debug_info, null, 2) + '</pre></details>';
                    }
                    noResults.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error fetching patient data:', error);
                loadingIndicator.style.display = 'none';
                noResults.style.display = 'block';
                noResults.innerHTML = `
                    <p>Error loading patient data. Please try again later.</p>
                    <p>Troubleshooting steps:</p>
                    <ul>
                        <li>Check if you're logged in. <a href="login.html">Login here</a> if needed.</li>
                        <li>Refresh the page and try again.</li>
                        <li>Clear your browser cache and cookies.</li>
                        <li><a href="debug-patient-data.php" target="_blank">Run diagnostic tool</a> to check system status.</li>
                        <li><a href="insert-test-data.php" target="_blank">Insert test data</a> to populate the database.</li>
                    </ul>
                    <details>
                        <summary>Technical Error Details</summary>
                        <pre>${error.toString()}</pre>
                    </details>
                `;
            });
    }
    
    /**
     * Render patient data as a table
     * @param {Array} analyses - Array of analysis data objects
     */
    function renderPatientTable(analyses) {
        // Clear previous results
        patientTableBody.innerHTML = '';
        
        // Process each analysis row
        analyses.forEach(analysis => {
            // Create table row
            const row = document.createElement('tr');
            
            // Determine status class based on classification
            const isHemorrhage = analysis.classification.toLowerCase().includes('hemorrhage detected');
            const statusClass = isHemorrhage ? 'status-positive' : 'status-negative';
            const statusText = isHemorrhage ? 'Hemorrhage' : 'Normal';
            
            // Format date as simple date without time for cleaner display
            const analysisDate = new Date(analysis.analysis_date.replace(/,/g, ''));
            const formattedDate = new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            }).format(analysisDate);
            
            // Format the row HTML with CSS classes for better presentation
            row.innerHTML = `
                <td class="patient-id-cell">${analysis.patient_id}</td>
                <td><span class="result-status ${statusClass}">${statusText}</span></td>
                <td>${parseFloat(analysis.confidence).toFixed(1)}%</td>
                <td class="scan-name-cell" title="${analysis.image_name}">${analysis.image_name}</td>
                <td class="date-cell">${formattedDate}</td>
                <td class="action-cell">
                    <button class="view-button" data-analysis-id="${analysis.analysis_id}">View Report</button>
                    <button class="download-button" data-analysis-id="${analysis.analysis_id}">Download</button>
                    <button class="delete-button" data-analysis-id="${analysis.analysis_id}" data-patient-id="${analysis.patient_id}">Delete</button>
                </td>
            `;
            
            // Add to table body
            patientTableBody.appendChild(row);
            
            // Add click handler for view button
            const viewButton = row.querySelector('.view-button');
            viewButton.addEventListener('click', function() {
                const analysisId = this.getAttribute('data-analysis-id');
                const analysisData = analyses.find(a => a.analysis_id == analysisId);
                if (analysisData) {
                    showPatientModal(analysisData);
                }
            });
            
            // Add click handler for download button
            const downloadButton = row.querySelector('.download-button');
            downloadButton.addEventListener('click', function() {
                const analysisId = this.getAttribute('data-analysis-id');
                downloadPatientRecord(analysisId);
            });
            
            // Add click handler for delete button
            const deleteButton = row.querySelector('.delete-button');
            deleteButton.addEventListener('click', function() {
                const analysisId = this.getAttribute('data-analysis-id');
                const patientId = this.getAttribute('data-patient-id');
                confirmDeleteRecord(analysisId, patientId, row);
            });
        });
    }
    
    /**
     * Show the patient details modal
     * @param {Object} analysis - The analysis data object
     */
    function showPatientModal(analysis) {
        // Set modal content
        document.getElementById('modalPatientId').textContent = 'Patient ID: ' + analysis.patient_id;
        document.getElementById('modalAnalysisResult').textContent = analysis.classification;
        document.getElementById('modalConfidence').textContent = parseFloat(analysis.confidence).toFixed(2) + '%';
        document.getElementById('modalAccuracy').textContent = parseFloat(analysis.accuracy).toFixed(2) + '%';
        document.getElementById('modalImageName').textContent = analysis.image_name;
        document.getElementById('modalAnalysisDate').textContent = analysis.analysis_date;
        
        // Set image sources
        document.getElementById('modalOriginalImage').src = analysis.image_url;
        document.getElementById('modalContourImage').src = analysis.contour_url;
        document.getElementById('modalEdgeImage').src = analysis.edge_url;
        document.getElementById('modalThresholdMaskImage').src = analysis.threshold_mask_url;
        document.getElementById('modalDamageOverlayImage').src = analysis.damage_overlay_url;
        
        // Add error handling for images
        const modalImages = document.querySelectorAll('#patientModal img');
        modalImages.forEach(img => {
            img.addEventListener('error', function() {
                this.src = 'assets/image-not-found.png';
                this.alt = 'Image failed to load';
            });
        });
        
        // Apply status class to result text
        const isHemorrhage = analysis.classification.toLowerCase().includes('hemorrhage detected');
        document.getElementById('modalAnalysisResult').className = isHemorrhage ? 'status-positive' : 'status-negative';
        
        // Show modal
        patientModal.style.display = 'block';
    }
    
    /**
     * Confirm and delete a patient record
     * @param {string} analysisId - The analysis ID to delete
     * @param {string} patientId - The patient ID for confirmation
     * @param {HTMLElement} row - The table row element to remove on success
     */
    function confirmDeleteRecord(analysisId, patientId, row) {
        if (confirm(`Are you sure you want to delete the record for patient ${patientId}?\nThis action cannot be undone.`)) {
            // Create form data
            const formData = new FormData();
            formData.append('analysis_id', analysisId);
            
            // Show loading state
            row.classList.add('deleting');
            const actionCell = row.querySelector('.action-cell');
            const originalContent = actionCell.innerHTML;
            actionCell.innerHTML = '<div class="loading-spinner" style="margin: 0 auto;"></div>';
            
            // Make delete request
            fetch('delete-patient-record.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Remove row with animation
                    row.style.animation = 'fadeOut 0.5s ease-out forwards';
                    setTimeout(() => {
                        row.remove();
                        
                        // Check if table is now empty
                        if (patientTableBody.childElementCount === 0) {
                            patientResults.style.display = 'none';
                            noResults.innerHTML = '<p>No patient records found. <a href="insert-test-data.php">Create test data</a> to continue.</p>';
                            noResults.style.display = 'block';
                        }
                        
                        // Show success message
                        const successMessage = document.createElement('div');
                        successMessage.className = 'alert-success';
                        successMessage.textContent = `Record for patient ${patientId} deleted successfully`;
                        successMessage.style.padding = '10px';
                        successMessage.style.backgroundColor = '#dff0d8';
                        successMessage.style.border = '1px solid #d6e9c6';
                        successMessage.style.borderRadius = '4px';
                        successMessage.style.marginBottom = '15px';
                        patientResults.parentNode.insertBefore(successMessage, patientResults);
                        
                        // Remove success message after 3 seconds
                        setTimeout(() => {
                            successMessage.style.opacity = '0';
                            successMessage.style.transition = 'opacity 0.5s ease-out';
                            setTimeout(() => successMessage.remove(), 500);
                        }, 3000);
                    }, 500);
                } else {
                    // Restore original content and show error
                    row.classList.remove('deleting');
                    actionCell.innerHTML = originalContent;
                    alert('Error deleting record: ' + (data.error || 'Unknown error'));
                    
                    // Re-attach event listeners
                    attachRowEventListeners(row);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                row.classList.remove('deleting');
                actionCell.innerHTML = originalContent;
                alert('Failed to delete record. Please try again.');
                
                // Re-attach event listeners
                attachRowEventListeners(row);
            });
        }
    }
    
    /**
     * Download a patient record
     * @param {string} analysisId - The analysis ID to download
     */
    function downloadPatientRecord(analysisId) {
        // Create a download link
        const downloadUrl = `download-patient-record.php?id=${analysisId}`;
        
        // Open the download in a new tab/window
        window.open(downloadUrl, '_blank');
    }
    
    /**
     * Re-attach event listeners to a row's buttons after error recovery
     * @param {HTMLElement} row - The table row element
     */
    function attachRowEventListeners(row) {
        // Re-attach view button event listener
        const viewButton = row.querySelector('.view-button');
        if (viewButton) {
            viewButton.addEventListener('click', function() {
                const analysisId = this.getAttribute('data-analysis-id');
                const analysisData = analyses.find(a => a.analysis_id == analysisId);
                if (analysisData) {
                    showPatientModal(analysisData);
                }
            });
        }
        
        // Re-attach download button event listener
        const downloadButton = row.querySelector('.download-button');
        if (downloadButton) {
            downloadButton.addEventListener('click', function() {
                const analysisId = this.getAttribute('data-analysis-id');
                downloadPatientRecord(analysisId);
            });
        }
        
        // Re-attach delete button event listener
        const deleteButton = row.querySelector('.delete-button');
        if (deleteButton) {
            deleteButton.addEventListener('click', function() {
                const analysisId = this.getAttribute('data-analysis-id');
                const patientId = this.getAttribute('data-patient-id');
                confirmDeleteRecord(analysisId, patientId, row);
            });
        }
    }
}); 
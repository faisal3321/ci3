// assets/js/utils.js

const ApiError = {
    // Centralized notification
    show: function(message, elementId = 'table-error') {
        const $errorDiv = $(`#${elementId}`);
        if ($errorDiv.length) {
            $errorDiv.stop(true, true)
                     .fadeIn()
                     .html(`⚠️ ${message}`)
                     .css({
                         'color': 'red',
                         'margin-bottom': '10px',
                         'font-weight': 'bold'
                     });
            
            // Auto-hide after 5 seconds
            setTimeout(() => $errorDiv.fadeOut(), 5000);
        }
    },

    // HTTP Status Resolver (The "Industry Standard" limit handler)
    handle: function(xhr, customMsg = null) {
        
        let msg = customMsg; 
        
       
        const status = xhr.status || xhr.statusCode;

        // If there's NO custom message, or if we want to override based on status
        if (!msg) {
            if (status === 429) {
                msg = "Rate limit reached. Please wait a moment before trying again.";
            } else if (status === 500) {
                msg = "Server error (500). Please contact the administrator.";
            } else if (status === 404) {
                msg = "The requested data was not found (404).";
            } else if (status === 0) {
                msg = "Network error. Please check your internet connection.";
            } else {
                msg = "Something went wrong. Try again.";
            }
        }
        // Send the final message to your show function
        this.show(msg);
    },

    // The "asyncHandler" Wrapper (Removes Try/Catch boilerplate)
    asyncWrapper: function(fn) {
        return (...args) => fn(...args).catch(err => {
            console.error("Global Error Log:", err);
            this.show("A connection error occurred.");
        });
    }
};

// Globally silence DataTables' default alert popups
if (typeof $.fn.dataTable !== 'undefined') {
    $.fn.dataTable.ext.errMode = 'none';
}
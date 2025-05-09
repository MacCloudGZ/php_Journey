document.addEventListener("DOMContentLoaded", () => {
    const tabs = document.querySelectorAll(".tab");
    const boxes = document.querySelectorAll(".box");

    // Get the saved tab ID from localStorage
    const savedTabId = localStorage.getItem("activeTab");
    let tabToActivate = null;

    // Find the tab element based on the saved ID
    if (savedTabId) {
        const potentialSavedTab = document.getElementById(savedTabId);
        // Check if the found element is actually a tab and exists
        if (potentialSavedTab && potentialSavedTab.classList.contains("tab")) {
            tabToActivate = potentialSavedTab;
        }
    }

    // If no saved tab or the saved ID was invalid, default to the first tab
    if (!tabToActivate && tabs.length > 0) {
        tabToActivate = tabs[0]; // Default to the first tab found
    }

    // --- Apply the determined active state ---

    // 1. Remove 'active' class from ALL tabs and boxes initially (clears HTML default)
    tabs.forEach(t => t.classList.remove("active"));
    boxes.forEach(box => box.classList.remove("active"));

    // 2. If a tab was found to activate, add the 'active' class
    if (tabToActivate) {
        tabToActivate.classList.add("active");

        // Find and activate the corresponding box
        const boxId = tabToActivate.id.replace("tab", "box");
        const boxToActivate = document.getElementById(boxId);
        if (boxToActivate) { // Ensure the box exists before adding class
            boxToActivate.classList.add("active");
        }
        // Ensure the currently active tab ID is saved in localStorage
        // This handles both cases: loading a saved tab or defaulting to the first one
        localStorage.setItem("activeTab", tabToActivate.id);
    } else {
         // Optional: Handle case where there are no tabs found at all
         console.warn("No tabs found on the page. Cannot activate any tab.");
         // Clear localStorage if no tabs could be activated, though less likely
         localStorage.removeItem("activeTab");
    }


    // --- Add click listeners for user interaction ---
    tabs.forEach(tab => {
        tab.addEventListener("click", () => {
            // Remove 'active' class from all tabs and boxes
            tabs.forEach(t => t.classList.remove("active"));
            boxes.forEach(box => box.classList.remove("active"));

            // Add 'active' class to the clicked tab and corresponding box
            tab.classList.add("active");
            const boxId = tab.id.replace("tab", "box");
            const boxToActivate = document.getElementById(boxId);
             if (boxToActivate) { // Ensure the box exists before adding class
                boxToActivate.classList.add("active");
            }

            // Save the active tab to localStorage
            localStorage.setItem("activeTab", tab.id);
        });
    });

    // The original activateDefaultTab function is no longer needed.

    // --- Add AJAX handler for the chat form ---
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const chatIframe = document.getElementById('chatIframe'); // Make sure iframe has this ID

    if (chatForm) {
        chatForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission (which would load PHP output)

            const formData = new FormData(chatForm); // Collect form data

            // --- Send data using Fetch API (modern way) ---
            fetch(chatForm.action, {
                method: chatForm.method,
                body: formData
            })
            .then(response => {
                // Check for HTTP errors (e.g., 404, 500)
                if (!response.ok) {
                    // Log the response text for debugging server-side errors
                    return response.text().then(text => { throw new Error('Network response was not ok: ' + response.status + ' ' + response.statusText + '\nServer response: ' + text); });
                }
                // If the PHP just outputs plain text like "Message sent successfully!"
                return response.text();
                // If PHP outputs JSON, use: return response.json();
            })
            .then(data => {
                console.log('Server response:', data);
                // --- Handle Success Response ---
                // Assuming success if fetch didn't throw an error and response ok

                // Clear the message input
                if (messageInput) {
                    messageInput.value = '';
                } else {
                     console.warn("Message input not found with ID 'messageInput'. Cannot clear.");
                }


                // Optional: Reset reCAPTCHA if using v2 checkbox
                // grecaptcha object is loaded by Google's API script
                if (typeof grecaptcha !== 'undefined' && grecaptcha.hasOwnProperty('reset')) {
                    // Assuming default reCAPTCHA widget, index 0
                    grecaptcha.reset();
                } else {
                    // Handle cases where reCAPTCHA API might not have loaded yet
                    console.warn("grecaptcha object not available or reset method missing. Cannot reset reCAPTCHA.");
                }


                // Reload the iframe content to show the new message
                if (chatIframe) {
                    // Using contentWindow.location.reload is generally preferred for iframes
                    // Pass true for a hard reload (bust cache)
                    chatIframe.contentWindow.location.reload(true);
                } else {
                    console.warn("Chat iframe not found with ID 'chatIframe'. Cannot reload.");
                }

                // Optional: Show a temporary success message to the user on the main page
                // You could display a small div instead of an alert
                // alert("Message sent!");

            })
            .catch(error => {
                // --- Handle Error Response ---
                console.error('Error submitting chat:', error);
                // Optional: Show an error message to the user
                alert("Failed to send message. See console for details.");
            });
        });
    } else {
        console.warn("Chat form not found with ID 'chatForm'. Cannot attach submit listener.");
    }

}); // End of DOMContentLoaded

// Function to switch tabs programmatically
function gotoTab(boxId) {
    // Basic validation for boxId
     if (typeof boxId !== 'string' || !boxId.startsWith('box-')) {
        console.error("Invalid boxId format for gotoTab:", boxId);
        return;
    }

    const tabId = boxId.replace("box", "tab");
    const tab = document.getElementById(tabId);

    if (tab) {
        // Simulate a click on the corresponding tab, which triggers its event listener
        tab.click();
    } else {
        console.warn("Could not find tab element for boxId:", boxId);
    }
}
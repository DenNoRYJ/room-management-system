/**
 * ============================================================
 * TUPV RMS — QR Scanner Utility Update
 * assets/js/qr.js
 * ============================================================
 */

document.addEventListener("DOMContentLoaded", () => {
    // Attach the scanner trigger to the designated button
    const startScannerBtn = document.getElementById("startScannerBtn");
    if (startScannerBtn) {
        startScannerBtn.addEventListener("click", startQRScanner);
    }
});

/**
 * Starts the device camera and scans for QR codes.
 * Prompts for camera permissions and outputs the scanned data to the form inputs.
 */
async function startQRScanner() {
    const reader = document.getElementById("reader");
    const studentIdInput = document.getElementById("student_id");
    const qrDataInput = document.getElementById("qr_data");

    // Display initial loading state
    reader.style.display = "block";
    reader.innerHTML = "<p>Opening camera...</p>";

    // Ensure the browser supports the BarcodeDetector API natively
    if (!("BarcodeDetector" in window)) {
        reader.innerHTML = "<p>Your browser does not support camera QR scanning. Please enter your Student ID manually.</p>";
        return;
    }

    try {
        // Request access to the rear-facing camera
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: "environment" }
        });

        // Create and configure a video element to stream the camera feed
        const video = document.createElement("video");
        video.setAttribute("autoplay", true);
        video.setAttribute("playsinline", true); // Required constraint for iOS Safari
        video.srcObject = stream;
        video.style.width = "100%";

        reader.innerHTML = "";
        reader.appendChild(video);

        // Initialize the detector specifically for QR codes
        const detector = new BarcodeDetector({ formats: ["qr_code"] });

        // Recursive scanning function
        const scan = async () => {
            try {
                const barcodes = await detector.detect(video);
                
                // If a QR code is successfully detected
                if (barcodes.length > 0) {
                    const qrText = barcodes[0].rawValue;
                    studentIdInput.value = qrText;
                    qrDataInput.value = qrText;

                    // Stop the camera stream to save resources and turn off camera light
                    stream.getTracks().forEach(track => track.stop());
                    reader.innerHTML = "<p>QR detected successfully.</p>";
                    return;
                }

                // Keep scanning on the next animation frame
                requestAnimationFrame(scan);
                
            } catch (err) {
                // Handle detection processing errors gracefully
                stream.getTracks().forEach(track => track.stop());
                reader.innerHTML = "<p>Unable to scan QR code. Please enter your Student ID manually.</p>";
            }
        };

        // Begin the continuous scanning loop
        scan();
        
    } catch (error) {
        // Handle permission denials or missing camera hardware
        reader.innerHTML = "<p>Camera access denied or unavailable. Please enter your Student ID manually.</p>";
    }
}
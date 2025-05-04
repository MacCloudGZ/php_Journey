document.addEventListener("DOMContentLoaded", () => {
    const tabs = document.querySelectorAll(".tab");
    const boxes = document.querySelectorAll(".box");

    tabs.forEach(tab => {
        tab.addEventListener("click", () => {
            // Remove 'active' class from all boxes
            boxes.forEach(box => box.classList.remove("active"));

            // Add 'active' class to the corresponding box
            const boxId = tab.id.replace("tab", "box");
            document.getElementById(boxId).classList.add("active");
        });
    });
});

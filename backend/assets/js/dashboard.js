// DASHBOARD JS
// handles functionality for the dashboard page


const hamburgerButton = document.getElementById("hamburger-button");
const drawerCloseButton = document.getElementById("drawer-panel-close");
const drawer = document.getElementById("drawer-panel");
const drawerBackdrop = document.getElementById("drawer-panel-backdrop");
const dashboardBody = document.getElementById("dashboard-body");

// DRAWER + HEADER CONTROL //

// update drawer visibility when hamburger button is clicked
hamburgerButton?.addEventListener("click", () => {
    setIsDrawerOpen(true);
});

drawerCloseButton?.addEventListener("click", () => {
    setIsDrawerOpen(false);
});

// Update drawer visibility
function setIsDrawerOpen(isDrawerOpen) {
    if (isDrawerOpen) {
        drawer?.classList.remove("-translate-x-full");
        drawer?.classList.add("translate-x-0");

        drawerBackdrop?.classList.remove("hidden");
    } else {
        drawer?.classList.remove("translate-x-0");
        drawer?.classList.add("-translate-x-full");

        drawerBackdrop?.classList.add("hidden");
    }
}    

// Close drawer if we click off drawer, but not if we click on the drawer itself?
drawerBackdrop?.addEventListener("click", (event) => {
        setIsDrawerOpen(false);
});

let menu = document.querySelector('#menu-icon');
let navbar = document.querySelector('.navbar');

menu.onclick = () => {
    menu.classList.toggle('bx-x');
    navbar.classList.toggle('active');
}
window.onscroll =()=>{
    menu.classList.remove('bx-x');
    navbar.classList.remove('active');


}
// Initialize ScrollReveal with faster, one-time animations
const sr = ScrollReveal({
    distance: '40px',
    duration: 800,
    delay: 50,
    reset: false, // Only animate once on page load
    origin: 'bottom',
    easing: 'ease-out'
})

// Animate elements with staggered delays (only once)
sr.reveal('.text', {delay: 100, origin: 'top', distance: '50px'})
sr.reveal('.form-container form', {delay: 150, origin: 'left', distance: '40px'})
sr.reveal('.heading', {delay: 80, origin: 'top', distance: '30px'})
sr.reveal('.ride-container .box', {delay: 100, origin: 'bottom', interval: 100, distance: '40px'})
sr.reveal('.services-container .box', {delay: 100, origin: 'bottom', interval: 80, distance: '40px'})
sr.reveal('.about-container', {delay: 120, origin: 'left', distance: '50px'})
sr.reveal('.reviewS-conrtainer', {delay: 100, origin: 'bottom', distance: '40px'})
sr.reveal('.newsletter .box', {delay: 100, origin: 'bottom', distance: '40px'})




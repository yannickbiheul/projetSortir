// MENU BURGER
const boutonBurger = document.querySelector('.bouton-burger');
const menuBurger = document.querySelector('.menu-burger');
const liens = document.querySelectorAll('.item');

boutonBurger.addEventListener('click', () => {
    menuBurger.classList.toggle('menu-burger-open');
});

for (let i = 0; i < liens.length; i++) {
    liens[i].addEventListener('click', function() {
        menuBurger.classList.toggle('menu-burger-open');
    })
}

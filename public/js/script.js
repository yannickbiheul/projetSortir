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

// FORMULAIRE LIEUX
const formLieu = document.querySelector('.formLieu');
const boutonForm = document.querySelector('#boutonForm');

boutonForm.addEventListener("click", function(e) {
    e.preventDefault();
    formLieu.classList.toggle('formLieu');
})

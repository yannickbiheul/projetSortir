const bouton = document.querySelector('.boutonLieu');
const inputNom = document.querySelector('.inputNom');
const inputRue = document.querySelector('.inputRue');
const inputLat = document.querySelector('.inputLat');
const inputLon = document.querySelector('.inputLon');
const inputVille = document.querySelector('.inputVille');

if (bouton) {
    bouton.addEventListener("click", ajouterLieu);
}


function ajouterLieu(event) {
    event.preventDefault();
    console.log('coucou');
}
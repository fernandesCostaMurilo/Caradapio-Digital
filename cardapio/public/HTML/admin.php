<?php
declare(strict_types=1);

require_once __DIR__ . '/../PHP/bootstrap.php';

iniciarSessao();
exigirAdmin();
$csrfToken = escaparHtml(tokenCsrf());
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Cardápio</title>
     <link rel="icon" type="image/png" href="logo.png.png">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin.css">
</head>

<body>
    <!-- BARRA DE ACESSIBILIDADE -->
<div id="barra-acess">
  <button onclick="aumentarFonte()" title="Aumentar fonte">A+</button>
  <button onclick="diminuirFonte()" title="Diminuir fonte">A-</button>
  <button onclick="toggleContraste()" title="Alto contraste" id="btn-contraste">◐ Contraste</button>
  <button onclick="toggleFonte()" title="Fonte para dislexia">Fonte acessível</button>
</div>


<script>
let tamanhoFonte = 100;

function aumentarFonte(){
  tamanhoFonte += 10;
  document.documentElement.style.fontSize = tamanhoFonte + '%';
  localStorage.setItem('fonte', tamanhoFonte);
}
function diminuirFonte(){
  if(tamanhoFonte > 70){
    tamanhoFonte -= 10;
    document.documentElement.style.fontSize = tamanhoFonte + '%';
    localStorage.setItem('fonte', tamanhoFonte);
  }
}
function toggleContraste(){
  document.body.classList.toggle('modo-contraste');
  const ativo = document.body.classList.contains('modo-contraste');
  document.getElementById('btn-contraste').classList.toggle('ativo', ativo);
  localStorage.setItem('contraste', ativo);
}
function toggleFonte(){
  document.body.classList.toggle('fonte-acessivel');
}

// carregar preferências salvas
(function(){
  const f = localStorage.getItem('fonte');
  if(f){ tamanhoFonte = parseInt(f); document.documentElement.style.fontSize = tamanhoFonte+'%'; }
  if(localStorage.getItem('contraste')==='true'){
    document.body.classList.add('modo-contraste');
    document.getElementById('btn-contraste')?.classList.add('ativo');
  }
})();
</script>
    <header>
        <div class="logo">🍽️ CARDÁPIO DIGITAL</div>
        <div style="display:flex;align-items:center;gap:14px">
            <h1>Painel do Cardápio</h1>
            <button id="novo-usuario" type="button" onclick="window.location.href='../PHP/cadastro.php'">＋ Novo usuário</button>
            <button id="logout">↪ Sair</button>
        </div>
    </header>

    <main id="cardapio" data-csrf-token="<?= $csrfToken ?>">
        <div class="loading">Carregando cardápio...</div>
    </main>

    <p id="mensagem"></p>

    <script>
        const menuUrl = "../PHP/menu.php";
        const csrfToken = document.getElementById("cardapio").dataset.csrfToken;
        let menu = [];
        function exibirMensagem(texto) {
            const msgEl = document.getElementById("mensagem");
            msgEl.textContent = texto;
            msgEl.classList.add("show");
            setTimeout(() => msgEl.classList.remove("show"), 3000);
        }
        async function carregarMenu() {
            try {
                const resposta = await fetch(menuUrl);
                const dados = await resposta.json();
                if (!resposta.ok) {
                    throw new Error(dados.mensagem || "Não foi possível carregar o cardápio.");
                }
                menu = dados;
                mostrarMenu();
            } catch (erro) {
                exibirMensagem(erro.message || "Não foi possível carregar o cardápio.");
            }
        }
        function escaparHtml(valor) {
            return String(valor ?? "").replace(/[&<>"']/g, caractere => ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;"
            })[caractere]);
        }
        function mostrarMenu() {
            const container = document.getElementById("cardapio");
            container.innerHTML = "";
            menu.forEach((item, index) => {
                const div = document.createElement("div");
                div.className = "dia";
                div.innerHTML = `
                    <h2>${escaparHtml(item.dia)}</h2>
                    <label>Prato</label>
                    <input id="titulo-${index}" value="${escaparHtml(item.titulo)}">
                    <label>Descrição</label>
                    <textarea id="descricao-${index}">${escaparHtml(item.descricao)}</textarea>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
                        <div><label>Calorias</label><input id="calorias-${index}" value="${escaparHtml(item.calorias)}"></div>
                        <div><label>Proteínas</label><input id="proteinas-${index}" value="${escaparHtml(item.proteinas)}"></div>
                        <div><label>Carboidratos</label><input id="carboidratos-${index}" value="${escaparHtml(item.carboidratos)}"></div>
                    </div>
                    <label>Alerta</label>
                    <textarea id="alerta-${index}">${escaparHtml(item.alerta)}</textarea>
                    <button class="salvar" onclick="salvarDia(${index})">💾 Salvar ${escaparHtml(item.dia)}</button>
                `;
                container.appendChild(div);
            });
        }
        async function salvarDia(index) {
            menu[index].titulo = document.getElementById(`titulo-${index}`).value;
            menu[index].descricao = document.getElementById(`descricao-${index}`).value;
            menu[index].calorias = document.getElementById(`calorias-${index}`).value;
            menu[index].proteinas = document.getElementById(`proteinas-${index}`).value;
            menu[index].carboidratos = document.getElementById(`carboidratos-${index}`).value;
            menu[index].alerta = document.getElementById(`alerta-${index}`).value;

            try {
                const resposta = await fetch(menuUrl, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-Token": csrfToken
                    },
                    body: JSON.stringify(menu)
                });
                const dados = await resposta.json();
                exibirMensagem(dados.mensagem || (resposta.ok ? "Salvo com sucesso!" : "Não foi possível salvar."));
            } catch (erro) {
                exibirMensagem(erro.message || "Não foi possível salvar o cardápio.");
            }
        }
        document.getElementById("logout").addEventListener("click", async () => {
            try {
                const resposta = await fetch("../PHP/logout.php", {
                    method: "POST",
                    headers: { "X-CSRF-Token": csrfToken }
                });
                if (resposta.ok) {
                    window.location.href = "../PHP/login.php";
                } else {
                    const dados = await resposta.json();
                    exibirMensagem(dados.mensagem || "Não foi possível sair.");
                }
            } catch (erro) {
                exibirMensagem(erro.message || "Não foi possível sair.");
            }
        });
        carregarMenu();
    </script>
    <!-- VLibras -->
<div vw class="enabled">
  <div vw-access-button class="active"></div>
  <div vw-plugin-wrapper>
    <div class="vw-plugin-top-wrapper"></div>
  </div>
</div>
<script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
<script>
  new window.VLibras.Widget('https://vlibras.gov.br/app');
</script>
</body>

</html>
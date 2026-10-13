let menu = [];


async function carregarMenu() {

    const resposta =
        await fetch("/api/menu");

    menu =
        await resposta.json();

    mostrarMenu();

}


function mostrarMenu() {

    const container =
        document.getElementById("cardapio");

    container.innerHTML = "";


    menu.forEach((item, index) => {

        const div =
            document.createElement("div");

        div.className = "dia";


        div.innerHTML = `

            <h2>${item.dia}</h2>

            <label>
                Prato
            </label>

            <input
                id="titulo-${index}"
                value="${item.titulo}"
            >

            <label>
                Descrição
            </label>

            <textarea
                id="descricao-${index}"
            >${item.descricao}</textarea>

            <label>
                Calorias
            </label>

            <input
                id="calorias-${index}"
                value="${item.calorias}"
            >

            <label>
                Proteínas
            </label>

            <input
                id="proteinas-${index}"
                value="${item.proteinas}"
            >

            <label>
                Carboidratos
            </label>

            <input
                id="carboidratos-${index}"
                value="${item.carboidratos}"
            >

            <label>
                Alerta
            </label>

            <textarea
                id="alerta-${index}"
            >${item.alerta}</textarea>

            <button
                class="salvar"
                onclick="salvarDia(${index})"
            >
                Salvar ${item.dia}
            </button>

        `;

        container.appendChild(div);

    });

}


async function salvarDia(index) {

    menu[index].titulo =
        document.getElementById(
            `titulo-${index}`
        ).value;


    menu[index].descricao =
        document.getElementById(
            `descricao-${index}`
        ).value;


    menu[index].calorias =
        document.getElementById(
            `calorias-${index}`
        ).value;


    menu[index].proteinas =
        document.getElementById(
            `proteinas-${index}`
        ).value;


    menu[index].carboidratos =
        document.getElementById(
            `carboidratos-${index}`
        ).value;


    menu[index].alerta =
        document.getElementById(
            `alerta-${index}`
        ).value;


    const resposta =
        await fetch("/api/admin/menu", {

            method: "PUT",

            headers: {
                "Content-Type":
                    "application/json"
            },

            body: JSON.stringify(menu)

        });


    const dados =
        await resposta.json();


    document.getElementById(
        "mensagem"
    ).textContent =
        dados.mensagem;

}


document
    .getElementById("logout")
    .addEventListener(
        "click",
        async () => {

            await fetch(
                "/api/logout",
                {
                    method: "POST"
                }
            );

            window.location.href =
                "/login.html";

        }
    );


carregarMenu();
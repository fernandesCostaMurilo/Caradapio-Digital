const bcrypt = require("bcrypt");
const fs = require("fs");
const path = require("path");

async function criarAdmin() {

    const senha = "Admin123!";

    const senhaHash = await bcrypt.hash(senha, 10);

    const usuario = [
        {
            id: 1,
            nome: "Administrador",
            email: "admin@escola.local",
            senha: senhaHash,
            role: "admin"
        }
    ];

    const caminho = path.join(
        __dirname,
        "data",
        "users.json"
    );

    fs.writeFileSync(
        caminho,
        JSON.stringify(usuario, null, 2),
        "utf8"
    );

    console.log("================================");
    console.log(" ADMINISTRADOR CRIADO");
    console.log("================================");
    console.log("");
    console.log("E-mail: admin@escola.local");
    console.log("Senha: cc!");
    console.log("");
    console.log("users.json atualizado.");
}

criarAdmin();
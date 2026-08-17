const express = require("express");
const session = require("express-session");
const bcrypt = require("bcrypt");
const fs = require("fs");
const path = require("path");

const app = express();

const PORT = 3000;  

// ======================================================
// CONFIGURAÇÕES
// ======================================================

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.use(
    session({
        secret: "troque-esta-chave-por-uma-chave-secreta",
        resave: false,
        saveUninitialized: false,
        cookie: {
            httpOnly: true,
            secure: false,
            maxAge: 1000 * 60 * 60
        }
    })
);

// Servir arquivos HTML, CSS e JS
app.use(express.static(path.join(__dirname, "public")));

// ======================================================
// ARQUIVOS
// ======================================================

const menuPath = path.join(__dirname, "data", "menu.json");
const usersPath = path.join(__dirname, "data", "users.json");

// ======================================================
// FUNÇÕES
// ======================================================

function lerMenu() {

    try {

        const dados = fs.readFileSync(menuPath, "utf8");

        return JSON.parse(dados);

    } catch (erro) {

        console.error("Erro ao ler menu:", erro);

        return [];
    }
}


function salvarMenu(menu) {

    fs.writeFileSync(
        menuPath,
        JSON.stringify(menu, null, 2),
        "utf8"
    );
}


function lerUsuarios() {

    try {

        const dados = fs.readFileSync(usersPath, "utf8");

        return JSON.parse(dados);

    } catch (erro) {

        console.error("Erro ao ler usuários:", erro);

        return [];
    }
}


// ======================================================
// MIDDLEWARE DE AUTENTICAÇÃO
// ======================================================

function verificarLogin(req, res, next) {

    if (!req.session.usuario) {

        return res.status(401).json({
            sucesso: false,
            mensagem: "Você precisa estar logado."
        });

    }

    next();
}


function verificarAdmin(req, res, next) {

    if (!req.session.usuario) {

        return res.status(401).json({
            sucesso: false,
            mensagem: "Você precisa estar logado."
        });

    }

    if (req.session.usuario.role !== "admin") {

        return res.status(403).json({
            sucesso: false,
            mensagem: "Acesso permitido somente ao administrador."
        });

    }

    next();
}


// ======================================================
// API - CARDÁPIO
// ======================================================

// Buscar cardápio
app.get("/api/menu", (req, res) => {

    const menu = lerMenu();

    res.json(menu);

});


// ======================================================
// API - LOGIN
// ======================================================

app.post("/api/login", async (req, res) => {

    try {

        const { email, senha } = req.body;

        if (!email || !senha) {

            return res.status(400).json({
                sucesso: false,
                mensagem: "Informe o e-mail e a senha."
            });

        }

        const usuarios = lerUsuarios();

        const usuario = usuarios.find(
            user => user.email.toLowerCase() === email.toLowerCase()
        );

        if (!usuario) {

            return res.status(401).json({
                sucesso: false,
                mensagem: "E-mail ou senha incorretos."
            });

        }

        const senhaCorreta = await bcrypt.compare(
            senha,
            usuario.senha
        );

        if (!senhaCorreta) {

            return res.status(401).json({
                sucesso: false,
                mensagem: "E-mail ou senha incorretos."
            });

        }

        req.session.usuario = {
            id: usuario.id,
            nome: usuario.nome,
            email: usuario.email,
            role: usuario.role
        };

        res.json({
            sucesso: true,
            mensagem: "Login realizado com sucesso.",
            usuario: req.session.usuario
        });

    } catch (erro) {

        console.error(erro);

        res.status(500).json({
            sucesso: false,
            mensagem: "Erro interno do servidor."
        });

    }

});


// ======================================================
// VERIFICAR USUÁRIO LOGADO
// ======================================================

app.get("/api/me", (req, res) => {

    if (!req.session.usuario) {

        return res.json({
            logado: false
        });

    }

    res.json({
        logado: true,
        usuario: req.session.usuario
    });

});


// ======================================================
// LOGOUT
// ======================================================

app.post("/api/logout", (req, res) => {

    req.session.destroy((erro) => {

        if (erro) {

            return res.status(500).json({
                sucesso: false,
                mensagem: "Não foi possível sair."
            });

        }

        res.json({
            sucesso: true,
            mensagem: "Logout realizado."
        });

    });

});


// ======================================================
// ATUALIZAR CARDÁPIO
// ======================================================

app.put("/api/admin/menu", verificarAdmin, (req, res) => {

    try {

        const novoMenu = req.body;

        if (!Array.isArray(novoMenu)) {

            return res.status(400).json({
                sucesso: false,
                mensagem: "Formato de cardápio inválido."
            });

        }

        salvarMenu(novoMenu);

        res.json({
            sucesso: true,
            mensagem: "Cardápio atualizado com sucesso.",
            menu: novoMenu
        });

    } catch (erro) {

        console.error(erro);

        res.status(500).json({
            sucesso: false,
            mensagem: "Erro ao salvar cardápio."
        });

    }

});


// ======================================================
// PÁGINA ADMIN
// ======================================================

app.get("/admin", verificarAdmin, (req, res) => {

    res.sendFile(
        path.join(__dirname, "public", "admin.html")
    );

});


// ======================================================
// SERVIDOR
// ======================================================

app.listen(PORT, () => {

    console.log("");
    console.log("=================================");
    console.log(" SERVIDOR DO CARDÁPIO DIGITAL");
    console.log("=================================");
    console.log("");
    console.log(`Servidor: http://localhost:${PORT}`);
    console.log(`Login:    http://localhost:${PORT}/login.html`);
    console.log(`Admin:    http://localhost:${PORT}/admin`);
    console.log("");
});
const estado = {
  periodo: 'manha',
  pratos: {
    cafe: [],
    almoco: []
  },
  tipoAtual: null
};

const listaCafe = document.getElementById('lista-cafe');
const listaAlmoco = document.getElementById('lista-almoco');
const modal = document.getElementById('modal-prato');
const inputPrato = document.getElementById('input-prato');

const opcoesCafeButtons = document.querySelectorAll('.opcao-item');

function atualizarTextoPeriodo() {
  const headers = document.querySelectorAll('.prato-header');
  const headerCafe = headers[0];
  const headerSegundo = headers[1];
  const tituloCafe = document.querySelector('.cesta-header .mini-title');

  if (estado.periodo === 'manha') {
    headerCafe.innerHTML = '<i>☕</i> CAFÉ DA MANHÃ';
    headerSegundo.innerHTML = '<i>🍽️</i> ALMOÇO';
  } else {
    headerCafe.innerHTML = '<i>☕</i> CAFÉ DA TARDE';
    headerSegundo.innerHTML = '<i>🍽️</i> JANTA';
  }

  if (tituloCafe) {
    tituloCafe.textContent = 'SUGESTÕES RÁPIDAS';
  }

  const texto = document.querySelector('.card-periodo.noite small');
  if (texto) {
    texto.textContent = estado.periodo === 'manha' ? 'Café da manhã e Almoço' : 'Café da tarde e Janta';
  }
}

// 1. ESCOLHER PERÍODO
document.querySelectorAll('.card-periodo').forEach(card => {
  card.addEventListener('click', () => {
    document.querySelectorAll('.card-periodo').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    estado.periodo = card.classList.contains('manha') ? 'manha' : 'noite';
    atualizarTextoPeriodo();
  });
});

// 2. ABRIR MODAL
document.querySelectorAll('.btn-add').forEach(btn => {
  btn.addEventListener('click', () => {
    estado.tipoAtual = btn.dataset.tipo;

    if (estado.periodo === 'manha') {
      document.getElementById('modal-titulo').innerText =
        estado.tipoAtual === 'cafe' ? 'Adicionar Café da Manhã' : 'Adicionar Almoço';
    } else {
      document.getElementById('modal-titulo').innerText =
        estado.tipoAtual === 'cafe' ? 'Adicionar Café da Tarde' : 'Adicionar Janta';
    }

    modal.classList.remove('hidden');
    inputPrato.value = '';
    inputPrato.focus();
  });
});

function fecharModal() {
  modal.classList.add('hidden');
  estado.tipoAtual = null;
}

document.getElementById('btn-cancelar').addEventListener('click', fecharModal);
modal.addEventListener('click', (e) => { if(e.target === modal) fecharModal() });

// 3. CONFIRMAR PRATO
function adicionarPrato(nome) {
  if (!nome.trim() ||!estado.tipoAtual) return;
  estado.pratos[estado.tipoAtual].push(nome.trim());
  render();
  fecharModal();
}

document.getElementById('btn-confirmar').addEventListener('click', () => {
  adicionarPrato(inputPrato.value);
});

inputPrato.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') adicionarPrato(inputPrato.value);
});

opcoesCafeButtons.forEach(button => {
  button.addEventListener('click', () => {
    const nome = button.dataset.nome;
    const jaExiste = estado.pratos.cafe.includes(nome);

    if (jaExiste) {
      estado.pratos.cafe = estado.pratos.cafe.filter(prato => prato !== nome);
      button.classList.remove('selected');
    } else {
      estado.pratos.cafe.push(nome);
      button.classList.add('selected');
    }

    render();
  });
});

// 4. SUGESTÕES RÁPIDAS
document.querySelectorAll('.chip').forEach(chip => {
  chip.addEventListener('click', () => {
    const nome = chip.innerText.replace(/.*\s/, '').trim() || chip.textContent.trim();
    // Lógica inteligente: Arroz, Feijão etc vai para Almoço, mas se o usuário quiser café, ele usa o botão
    // Vamos perguntar onde adicionar se for ambíguo, ou adicionar direto no almoço
    const textoLimpo = chip.textContent.replace(/[^a-zA-ZÀ-ÿ\s]/g, '').trim();

    // Se já tem algo no café e nada no almoço, adiciona no almoço e vice-versa
    // Simplificado: adiciona no almoço por padrão
    estado.pratos.almoco.push(textoLimpo);
    render();

    chip.animate([{transform:'scale(1)'},{transform:'scale(0.9)'},{transform:'scale(1)'}], {duration:200});
  });
});

// 5. RENDERIZAR LISTAS
function render() {
  renderLista('cafe', listaCafe);
  renderLista('almoco', listaAlmoco);
}

function renderLista(tipo, container) {
  const pratos = estado.pratos[tipo];
  if (pratos.length === 0) {
    container.innerHTML = 'Nenhum prato selecionado';
    container.className = 'empty';
    return;
  }
  container.className = 'lista-pratos';
  container.innerHTML = pratos.map((nome, index) => `
    <div class="prato-item">
      <span>${nome}</span>
      <button onclick="removerPrato('${tipo}', ${index})">x</button>
    </div>
  `).join('');
}

window.removerPrato = (tipo, index) => {
  estado.pratos[tipo].splice(index, 1);
  render();
}

// 6. SALVAR CARDÁPIO
document.querySelector('.btn-salvar').addEventListener('click', () => {
  const observacao = document.querySelector('.obs-wrapper input').value;

  if (estado.pratos.cafe.length === 0 && estado.pratos.almoco.length === 0) {
    alert('Adicione pelo menos um prato antes de salvar!');
    return;
  }

  const cardapio = {
    id: Date.now(),
    data: new Date().toLocaleDateString('pt-BR'),
    periodo: estado.periodo,
    pratos: {...estado.pratos},
    observacao
  };

  const salvos = JSON.parse(localStorage.getItem('cardapios') || '[]');
  salvos.push(cardapio);
  localStorage.setItem('cardapios', JSON.stringify(salvos));

  // feedback visual
  const btn = document.querySelector('.btn-salvar');
  btn.innerText = '✓ CARDÁPIO SALVO!';
  btn.style.background = '#2e7d32';

  setTimeout(() => {
    btn.innerHTML = '💾 SALVAR CARDÁPIO';
    btn.style.background = '';
    // limpa
    estado.pratos.cafe = [];
    estado.pratos.almoco = [];
    document.querySelector('.obs-wrapper input').value = '';
    render();
  }, 1500);

  console.log('Cardápio salvo:', cardapio);
});

render()

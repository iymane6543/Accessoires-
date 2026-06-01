document.addEventListener('DOMContentLoaded', function () {
  const chatbot = document.getElementById('chatbot');
  const openChat = document.getElementById('openChat');
  const closeChat = document.getElementById('closeChat');
  const chatForm = document.getElementById('chatForm');
  const chatInput = document.getElementById('chatInput');
  const chatMessages = document.getElementById('chatMessages');
  const aiSearchForm = document.getElementById('aiSearchForm');
  const aiQuestion = document.getElementById('aiQuestion');
  const aiAnswer = document.getElementById('aiAnswer');

  function endpoint() {
    const url = new URL('index.php', window.location.href);
    url.search = '';
    url.searchParams.set('page', 'chatbot');
    return url.toString();
  }

  function addMessage(text, className, asHtml) {
    if (!chatMessages) return;
    const div = document.createElement('div');
    div.className = className;
    if (asHtml) {
      div.innerHTML = text;
    } else {
      div.textContent = text;
    }
    chatMessages.appendChild(div);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  async function askBot(question) {
    const response = await fetch(endpoint(), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: 'question=' + encodeURIComponent(question)
    });
    const data = await response.json();
    return data.reponse || 'Je ne sais pas encore repondre a cette question.';
  }

  if (openChat && chatbot) {
    openChat.addEventListener('click', function () {
      chatbot.hidden = false;
      if (chatInput) chatInput.focus();
    });
  }

  if (closeChat && chatbot) {
    closeChat.addEventListener('click', function () {
      chatbot.hidden = true;
    });
  }

  if (chatForm && chatInput) {
    chatForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      const question = chatInput.value.trim();
      if (!question) return;

      chatInput.value = '';
      addMessage(question, 'user-message', false);
      addMessage('...', 'bot-message loading', false);
      const loading = chatMessages.querySelector('.loading:last-child');

      try {
        const answer = await askBot(question);
        if (loading) loading.remove();
        addMessage(answer, 'bot-message', true);
      } catch (error) {
        if (loading) loading.remove();
        addMessage('Erreur de connexion au chatbot.', 'bot-message', false);
      }
    });
  }

  if (aiSearchForm && aiQuestion && aiAnswer) {
    aiSearchForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      const question = aiQuestion.value.trim();
      if (!question) return;

      aiAnswer.textContent = 'Recherche en cours...';
      try {
        aiAnswer.innerHTML = await askBot(question);
      } catch (error) {
        aiAnswer.textContent = 'Erreur de connexion au chatbot.';
      }
    });
  }

  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      const message = form.getAttribute('data-confirm') || 'Confirmer cette action ?';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });
});

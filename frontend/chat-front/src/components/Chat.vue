<template>
  <div>
    <h1>Chat simples</h1>

    <p>Status: <strong>{{ status }}</strong></p>

    <ul>
      <li v-for="(msg, index) in messages" :key="index">
        {{ msg }}
      </li>
    </ul>

    <input
      v-model="text"
      placeholder="Digite algo"
      :disabled="status !== 'conectado'"
      @keyup.enter="send"
    />

    <button
      @click="send"
      :disabled="status !== 'conectado'"
    >
      Enviar
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const messages = ref([])
const text = ref('')
const status = ref('conectando...')
let socket = null

onMounted(() => {
  socket = new WebSocket('ws://localhost:9502')

  socket.onopen = () => {
    status.value = 'conectado'
    console.log('🟢 WebSocket conectado')
  }

  socket.onmessage = (event) => {
    messages.value.push(event.data)
  }

  socket.onclose = () => {
    status.value = 'desconectado'
    console.log('🔴 WebSocket fechado')
  }

  socket.onerror = () => {
    status.value = 'erro'
    console.log('⚠️ Erro no WebSocket')
  }
})

function send() {
  if (!text.value || status.value !== 'conectado') return
  socket.send(text.value)
  text.value = ''
}
</script>

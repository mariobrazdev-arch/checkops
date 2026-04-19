# Camera — Componente Crítico

## ⚠️ NUNCA modificar sem ler este arquivo

`CameraCaptura.vue` implementa a RN-03 (regra de negócio crítica):
> Foto APENAS via câmera nativa — galeria bloqueada.

## Comportamento obrigatório
1. Usa `getUserMedia({ video: { facingMode: 'environment' } })`
2. **Proibido**: `<input type="file">`, `accept="image/*"`, galeria
3. Captura → converte para base64 → emite evento com metadados
4. Metadados obrigatórios no emit:
   ```js
   emit('capturada', {
     base64: '...',         // imagem
     timestamp: Date.now(), // foto_timestamp
     deviceId: '...',       // foto_device_id (do track)
     lat: null,             // foto_lat (se GPS liberado)
     lng: null,             // foto_lng
   })
   ```
5. Solicitar geolocalização ANTES de abrir câmera
6. GPS negado → aceitar foto mas `lat/lng = null`

## Interface do componente
```vue
<!-- Uso -->
<CameraCaptura
  :aberta="modalAberto"
  @capturada="onFotoCapturada"
  @cancelada="modalAberto = false"
/>
```

## Futuro Capacitor
Quando migrar para Capacitor, trocar `getUserMedia` por `@capacitor/camera`
mantendo o mesmo contrato de emit — nenhum código consumidor muda.

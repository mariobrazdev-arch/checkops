import { ref } from 'vue'

/**
 * Gerencia erros de validação 422 retornados pelo Laravel.
 *
 * Uso:
 *   const { erros, temErro, mensagemErro, definirErros, limpar } = useFormErrors()
 *
 *   // No catch do formulário:
 *   if (error.response?.status === 422) {
 *     definirErros(error.response.data.errors)
 *   }
 *
 *   // No template:
 *   <small v-if="temErro('email')" role="alert">{{ mensagemErro('email') }}</small>
 */
export function useFormErrors() {
  /** @type {import('vue').Ref<Record<string, string[]>>} */
  const erros = ref({})

  /**
   * Recebe `errors` do payload 422 do Laravel:
   *   { campo: ['mensagem 1', 'mensagem 2'] }
   */
  function definirErros(laravelErrors = {}) {
    erros.value = laravelErrors
  }

  function temErro(campo) {
    return !!erros.value[campo]?.length
  }

  function mensagemErro(campo) {
    return erros.value[campo]?.[0] ?? ''
  }

  function limpar() {
    erros.value = {}
  }

  return { erros, definirErros, temErro, mensagemErro, limpar }
}

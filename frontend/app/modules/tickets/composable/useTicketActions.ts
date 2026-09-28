import { toast } from 'vue-sonner'
import type { TicketCreateData, TicketUpdateData } from '../ports/ticket-repository'
import { ApiError } from '~/modules/core/ports/http'

export function useTicketActions() {
  const { $ticketRepository } = useNuxtApp()
  const { reload: reloadList } = useTicketList()
  const { reload: reloadBoard } = useTicketBoard()

  async function afterMutation(): Promise<void> {
    await Promise.all([reloadList(), reloadBoard()])
  }

  async function create(data: TicketCreateData): Promise<ApiError | null> {
    try {
      await $ticketRepository.create(data)
      toast.success('Chamado criado com sucesso.')
      await afterMutation()
      return null
    }
    catch (error) {
      if (error instanceof ApiError) {
        if (!error.isValidation) {
          toast.error(error.message)
        }
        return error
      }
      toast.error('Não foi possível criar o chamado.')
      return null
    }
  }

  async function update(id: number, data: TicketUpdateData): Promise<ApiError | null> {
    try {
      await $ticketRepository.update(id, data)
      toast.success('Chamado atualizado com sucesso.')
      await afterMutation()
      return null
    }
    catch (error) {
      if (error instanceof ApiError) {
        if (!error.isValidation) {
          toast.error(error.message)
        }
        return error
      }
      toast.error('Não foi possível atualizar o chamado.')
      return null
    }
  }

  async function destroy(id: number): Promise<boolean> {
    try {
      await $ticketRepository.destroy(id)
      toast.success('Chamado excluído com sucesso.')
      await afterMutation()
      return true
    }
    catch (error) {
      const msg = error instanceof ApiError ? error.message : 'Não foi possível excluir o chamado.'
      toast.error(msg)
      return false
    }
  }

  return { create, update, destroy }
}

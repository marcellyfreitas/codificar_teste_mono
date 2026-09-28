import type {
  AuthRepository,
  Credentials,
  Registration,
  User,
} from '../ports/auth-repository'
import type { HttpPort } from '../../core/ports/http'

/**
 * O cookie HttpOnly é gerenciado pelo servidor Nitro: os handlers
 * login.post.ts e register.post.ts interceptam as respostas, extraem o
 * access_token, gravam no cookie e devolvem apenas { message, data: { user } }.
 *
 * Este adapter só extrai o usuário da resposta — nunca vê o token.
 */
interface AuthEnvelope {
  message: string,
  data: { user: User },
}

interface MeEnvelope {
  data: User,
}

export class HttpAuthRepository implements AuthRepository {
  constructor(private readonly http: HttpPort) {}

  async login(credentials: Credentials): Promise<User> {
    const envelope = await this.http.post<AuthEnvelope>('/login', credentials)
    return envelope.data.user
  }

  async register(payload: Registration): Promise<User> {
    const envelope = await this.http.post<AuthEnvelope>('/register', payload)
    return envelope.data.user
  }

  async me(): Promise<User> {
    const envelope = await this.http.get<MeEnvelope>('/me')
    return envelope.data
  }
}

import { unwrap } from '../../core/utils/response'

import type {
  AuthRepository,
  Credentials,
  Registration,
  Session,
  User,
} from '../ports/auth-repository'
import type { HttpPort } from '../../core/ports/http'

interface AuthEnvelope {
  message: string,
  data: {
    user: User,
    access_token: string,
    token_type: string,
  },
}

interface MeEnvelope {
  data: User,
}

/**
 * Autenticação sobre o port de HTTP. Não chama `$fetch` diretamente — o
 * `core` é o único lugar do app que fala com a rede, e este adapter só sabe
 * *o que* pedir, não *como*.
 */
export class HttpAuthRepository implements AuthRepository {
  constructor(private readonly http: HttpPort) {}

  async login(credentials: Credentials): Promise<Session> {
    const envelope = await this.http.post<AuthEnvelope>('/login', credentials)

    return this.toSession(envelope)
  }

  async register(payload: Registration): Promise<Session> {
    const envelope = await this.http.post<AuthEnvelope>('/register', payload)

    return this.toSession(envelope)
  }

  async me(): Promise<User> {
    const envelope = await this.http.get<MeEnvelope>('/me')

    return envelope.data
  }

  private toSession(envelope: AuthEnvelope): Session {
    const { user, access_token } = unwrap(envelope)

    return { user, token: access_token }
  }
}

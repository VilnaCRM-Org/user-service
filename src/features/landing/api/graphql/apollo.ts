import {
  ApolloClient,
  InMemoryCache,
  NormalizedCacheObject,
} from '@apollo/client';

interface User {
  initials: string;
  email: string;
  password: string;
}

const client: ApolloClient<NormalizedCacheObject> = new ApolloClient({
  uri: `https://${process.env.GRAPHQL_API_URL}`,
  cache: new InMemoryCache(),
});

export class createUser implements User {
  public initials: string;

  public email: string;

  public password: string;

  constructor(initials: string, email: string, password: string) {
    this.initials = initials;
    this.email = email;
    this.password = password;
  }
}

export default client;

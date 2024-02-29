import {
  ApolloClient,
  InMemoryCache,
  NormalizedCacheObject,
} from '@apollo/client';

const client: ApolloClient<NormalizedCacheObject> = new ApolloClient({
  uri: 'https://localhost/api/graphql',
  cache: new InMemoryCache(),
});

export default client;

import {
  ApolloClient,
  InMemoryCache,
  NormalizedCacheObject,
} from '@apollo/client';

const client: ApolloClient<NormalizedCacheObject> = new ApolloClient({
  uri: `https://${process.env.GRAPHQL_API_URL}`,
  cache: new InMemoryCache(),
});

export default client;

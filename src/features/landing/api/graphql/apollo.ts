import {
  ApolloClient,
  InMemoryCache,
  NormalizedCacheObject,
} from '@apollo/client';

import i18n from 'i18n';

const { language } = i18n;

const client: ApolloClient<NormalizedCacheObject> = new ApolloClient({
  uri: process.env.NEXT_PUBLIC_GRAPHQL_API_URL,
  cache: new InMemoryCache(),
  headers: {
    'Accept-Language': language || 'en-US',
  },
});

export default client;

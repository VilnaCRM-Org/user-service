import { ApolloProvider } from '@apollo/client';
import { Box, Container } from '@mui/material';

import client from '../../api/graphql/apollo';
import { AboutUs } from '../AboutUs';
import { AuthSection } from '../AuthSection';
import { BackgroundImages } from '../BackgroundImages';
import { Footer } from '../Footer';
import { ForWhoSection } from '../ForWhoSection';
import { Header } from '../Header';
import { Possibilities } from '../Possibilities';
import { WhyUs } from '../WhyUs';

function Landing(): React.ReactElement {
  return (
    <ApolloProvider client={client}>
      <Header />
      <Box sx={{ position: 'relative' }}>
        <BackgroundImages />
        <AboutUs />
        <Container maxWidth="xl">
          <WhyUs />
        </Container>
      </Box>
      <ForWhoSection />
      <Container maxWidth="xl">
        <Possibilities />
      </Container>
      <AuthSection />
      <Footer />
    </ApolloProvider>
  );
}

export default Landing;

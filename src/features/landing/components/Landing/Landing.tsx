import { Box, Container } from '@mui/material';
import Head from 'next/head';

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
    <>
      <Head>
        <title>VilnaCRM</title>
        <meta
          name="description"
          content="The first Ukrainian open source CRM"
        />
      </Head>
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
    </>
  );
}

export default Landing;

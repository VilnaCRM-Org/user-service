import { Container } from '@mui/material';
import Head from 'next/head';

import { AboutUs } from '../AboutUs';
import { AuthSection } from '../AuthSection';
import { Footer } from '../Footer';
import { ForWhoSection } from '../ForWhoSection';
import { Header } from '../Header';
import { Possibilities } from '../Possibilities';
import { WhyUs } from '../WhyUs';

function Landing() {
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
      <main>
        <Container maxWidth="xl">
          <AboutUs />
          <WhyUs />
        </Container>
        <ForWhoSection />
        <Container maxWidth="xl">
          <Possibilities />
        </Container>
        <AuthSection />
      </main>
      <Footer />
    </>
  );
}

export default Landing;

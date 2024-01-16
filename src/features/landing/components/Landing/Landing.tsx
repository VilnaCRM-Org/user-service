import { Container } from '@mui/material';

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
      <Header />
      <Container maxWidth="xl">
        <AboutUs />
        <WhyUs />
      </Container>
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

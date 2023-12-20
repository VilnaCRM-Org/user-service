import { Container } from '@mui/material';

import AboutUs from '../AboutUs';
import Header from '../Header';
import Possibilities from '../Possibilities';
import WhyUs from '../WhyUs/WhyUs';

function Landing() {
  return (
    <Container maxWidth="xl">
      <Header />
      <AboutUs />
      <WhyUs />
      <Possibilities />
    </Container>
  );
}

export default Landing;

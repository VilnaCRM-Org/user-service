import { Box, Container } from '@mui/material';
// eslint-disable-next-line import/order
import Golos from '@next/font/local';
import Head from 'next/head';

import { AboutUs } from '../AboutUs';
import { AuthSection } from '../AuthSection';
import { BackgroundImages } from '../BackgroundImages';
import { Footer } from '../Footer';
import { ForWhoSection } from '../ForWhoSection';
import { Header } from '../Header';
import { Possibilities } from '../Possibilities';
import { WhyUs } from '../WhyUs';

export const golos = Golos({
  src: '../../assets/fonts/Golos/GolosText-Regular.ttf',
  weight: '400',
  style: 'normal',
  display: 'swap',
  variable: '--font-golos',
});

// export const golos1 = Golos({
//   src: '../../assets/fonts/Golos/GolosText-Medium.ttf',
//   weight: '500',
//   style: 'normal',
//   display: 'swap',
//   variable: '--font-golos',
// });
// export const golos2 = Golos({
//   src: '../../assets/fonts/Golos/GolosText-SemiBold.ttf',
//   weight: '600',
//   style: 'normal',
//   display: 'swap',
//   variable: '--font-golos',
// });
// export const golos3 = Golos({
//   src: '../../assets/fonts/Golos/GolosText-Bold.ttf',
//   weight: '700',
//   style: 'normal',
//   display: 'swap',
//   variable: '--font-golos',
// });
// export const golos4 = Golos({
//   src: '../../assets/fonts/Golos/GolosText-ExtraBold.ttf',
//   weight: '800',
//   style: 'normal',
//   display: 'swap',
//   variable: '--font-golos',
// });
// export const golos5 = Golos({
//   src: '../../assets/fonts/Golos/GolosText-Black.ttf',
//   weight: '900',
//   style: 'normal',
//   display: 'swap',
//   variable: '--font-golos',
// });

// const golos = Golos({
//   src: [
//     {
//       path: '../../assets/fonts/Golos/GolosText-Regular.ttf',
//       weight: '400',
//     },
//     {
//       path: '../../assets/fonts/Golos/GolosText-Medium.ttf',
//       weight: '500',
//     },
//     {
//       path: '../../assets/fonts/Golos/GolosText-Bold.ttf',
//       weight: '600',
//     },
//     {
//       path: '../../assets/fonts/Golos/GolosText-Bold.ttf',
//       weight: '700',
//     },
//     {
//       path: '../../assets/fonts/Golos/GolosText-ExtraBold.ttf',
//       weight: '800',
//     },
//     {
//       path: '../../assets/fonts/Golos/GolosText-Black.ttf',
//       weight: '900',
//     },
//   ],
// });

function Layout() {
  return (
    <Box className={golos.className}>
      <Head>
        <title>VilnaCRM</title>
        <meta
          name="description"
          content="The first Ukrainian open source CRM"
        />
      </Head>
      <Header />
      <main>
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
      </main>
      <Footer />
    </Box>
  );
}

export default Layout;

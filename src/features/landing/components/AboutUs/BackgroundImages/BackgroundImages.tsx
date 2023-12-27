import { Box } from '@mui/material';
import React from 'react';

import VectorIcon from '../../../assets/img/AboutVilna/Vector.svg';

// bad solution, need to be refactored

function BackgroundImages() {
  return (
    <Box
      sx={{
        backgroundImage: `url(${VectorIcon.src})`,
        backgroundSize: 'cover',
        backgroundRepeat: 'no-repeat',
        width: '100%',
        maxWidth: '1771px',
        height: '900px',
        zIndex: '-2',
      }}
    />
  );
}

export default BackgroundImages;
// import { Box, Container } from '@mui/material';
// import React from 'react';

// import VectorIcon from '../../../assets/img/AboutVilna/Vector.svg';

// // bad solution, need to be refactored

// function BackgroundImages() {
//   return (
//     <>
//       <Box sx={{ position: 'relative',width: '100%', margin: '0 auto' }}>
//         <Box
//           sx={{
//             margin: '0 auto',
//             backgroundImage: `url(${VectorIcon.src})`,
//             backgroundRepeat: 'no-repeat',
//             top: '120px',
//             width: '100%',
//             maxwidth: '1440px',
//             height: '900px',
//             zIndex: '-2',
//           }}
//         />
//       </Box>
//       <Container>
//         <Box
//           sx={{
//             background:
//               'linear-gradient(0deg, rgba(245,255,0,1) 0%, rgba(255,252,0,1) 29%, rgba(0,236,255,1) 59%, rgba(0,164,255,1) 100%)',
//             width: '100%',
//             maxwidth: '1190px',
//             height: '493px',
//             zIndex: '-1',
//             top: '580px',
//             borderRadius: '48px',
//           }}
//         />
//       </Container>
//     </>
//   );
// }

// export default BackgroundImages;

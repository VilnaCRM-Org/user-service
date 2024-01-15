import VectorIcon from '../../assets/svg/service-hub/bg-lg.svg';
import VectorIconMd from '../../assets/svg/service-hub/bg-md.svg';

export const serviceHubStyles = {
  wrapper: {
    background: '#FBFBFB',
    maxWidth: '100dvw',
    overflow: 'hidden',
  },

  content: {
    pt: '100px',
    position: 'relative',
  },

  mainImage: {
    backgroundImage: `url(${VectorIcon.src})`,
    backgroundSize: 'contain',
    backgroundRepeat: 'no-repeat',
    backgroundPosition: 'center',
    width: '100dvw',
    maxWidth: '830px',
    height: '715px',
    zIndex: '1',
    position: 'absolute',
    top: '12.3%',
    right: '-6.4%',
    '@media (max-width: 1130.98px)': {
      backgroundImage: `url(${VectorIconMd.src})`,
      width: '100dvw',
      maxWidth: '762px',
      height: '663px',
      top: '3.7%',
      right: '-9%',
    },
    '@media (max-width: 425.98px)': {
      width: '420px',
      top: '16.5%',
      right: '-27%',
    },
  },
  line: {
    background: '#fff',
    maxHeight: '100px',
    position: 'relative',
    zIndex: 2,
    marginTop: '-60px',
    '@media (max-width: 1130.98px)': {
      maxnHeight: '179px',
      marginTop: '-138px',
    },
    '@media (max-width: 425.98px)': {
      display: 'none',
    },
  },
};

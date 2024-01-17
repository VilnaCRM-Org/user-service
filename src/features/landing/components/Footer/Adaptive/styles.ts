export const adaptiveStyles = {
  wrapper: {
    display: 'none',
    marginBottom: '20px',
    borderTop: '1px solid  #E1E7EA',
    background: ' #FFF',
    boxShadow:
      ' 0px -5px 46px 0px rgba(198, 209, 220, 0.25), 0px -5px 46px 0px rgba(198, 209, 220, 0.25)',
    '@media (max-width: 767.98px)': {
      display: 'inline-block',
    },
  },
  gmailText: {
    color: '#1B2327',
    textAlign: 'center',
    width: '100%',
    '@media (max-width: 767.98px)': {
      fontFamily: 'Golos',
      fontSize: '18px',
      fontStyle: 'normal',
      fontWeight: '600',
      lineHeight: 'normal',
    },
  },
  gmailWrapper: {
    padding: '8px 16px',
    borderRadius: '8px',
    background: '#fff',
    border: '1px solid  #D0D4D8',
    '@media (max-width: 767.98px)': {
      padding: '14px 0 15px',
    },
  },
  copyright: {
    paddingBottom: '20px',
    color: '#404142',
    textAlign: 'center',
    width: '100%',
    mt: '16px',
  },
};

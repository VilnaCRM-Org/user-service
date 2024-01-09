export const adaptiveStyles = {
  wrapper: {
    marginBottom: '20px',
  },
  gmailText: {
    color: '#1B2327',
    textAlign: 'center',
    width: '100%',
    '@media (max-width: 768.98px)': {
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
    '@media (max-width: 768.98px)': {
      padding: '15px 0',
    },
  },
  copyright: {
    color: '#404142',
    textAlign: 'center',
    width: '100%',
    mt: '16px',
  },
};

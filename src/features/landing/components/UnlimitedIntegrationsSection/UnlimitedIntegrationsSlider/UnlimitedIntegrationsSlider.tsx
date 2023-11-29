import { Box, Container, MobileStepper } from '@mui/material';
import { useState } from 'react';
import SwipeableViews from 'react-swipeable-views';

import { IUnlimitedIntegrationsItem } from '@/features/landing/types/unlimited-integrations/types';

import UnlimitedIntegrationsCardItem from '../UnlimitedIntegrationsCardItem/UnlimitedIntegrationsCardItem';

const styles = {
  mobileStepper: {
    display: 'flex',
    justifyContent: 'center',
    marginTop: '25px',
  },
};

// TODO: Change Carousel to a newer one
export default function UnlimitedIntegrationsSlider({
  cardItems,
}: {
  cardItems: IUnlimitedIntegrationsItem[];
}) {
  const [activeStep, setActiveStep] = useState(0);
  const [, setCurrentActiveItem] = useState(cardItems[0]);
  const maxSteps = cardItems.length;

  const handleStepChange = (step: number) => {
    setCurrentActiveItem(cardItems[step]);
    setActiveStep(step);
  };

  return (
    <Box sx={{ padding: '0' }}>
      <Container style={{ padding: '0' }}>
        <SwipeableViews
          axis="x"
          index={activeStep}
          onChangeIndex={handleStepChange}
          enableMouseEvents
        >
          {cardItems.map((item) => (
            <Box
              key={item.id}
              sx={{
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'center',
                height: '100%',
              }}
            >
              <UnlimitedIntegrationsCardItem
                cardItem={item}
                style={{
                  height: '100%',
                  overflow: 'hidden',
                  width: '100%',
                  margin: '0 5px',
                }}
              />
            </Box>
          ))}
        </SwipeableViews>

        <MobileStepper
          steps={maxSteps}
          position="static"
          activeStep={activeStep}
          nextButton={null}
          backButton={null}
          sx={{ ...styles.mobileStepper }}
        />
      </Container>
    </Box>
  );
}

import { useState } from 'react';
import { Box, Container, MobileStepper } from '@mui/material';
import SwipeableViews from 'react-swipeable-views';
import { autoPlay } from 'react-swipeable-views-utils';

import { IUnlimitedIntegrationsItem } from '@/features/landing/types/unlimited-integrations/types';
import {
  UnlimitedIntegrationsCardItem,
} from '../UnlimitedIntegrationsCardItem/UnlimitedIntegrationsCardItem';

const AutoPlaySwipeableViews = autoPlay(SwipeableViews);

// TODO: Change Carousel to a newer one
export function UnlimitedIntregrationsSlider({ cardItems }: {
  cardItems: IUnlimitedIntegrationsItem[]
}) {
  const [activeStep, setActiveStep] = useState(0);
  const [currentActiveItem, setCurrentActiveItem] = useState(cardItems[0]);
  const maxSteps = cardItems.length;

  const handleStepChange = (step: number) => {
    setCurrentActiveItem(cardItems[step]);
    setActiveStep(step);
  };

  return (
    <Box sx={{ padding: '0' }}>
      <Container>
        <AutoPlaySwipeableViews
          axis='x'
          index={activeStep}
          onChangeIndex={handleStepChange}
          enableMouseEvents
        >
          {cardItems.map((item, index) => (
            <Box key={index} sx={{
              display: 'flex',
              justifyContent: 'center',
              alignItems: 'center',
              height: '100%',
            }}>
              <UnlimitedIntegrationsCardItem
                cardItem={item}
                style={{
                  height: '100%',
                  overflow: 'hidden',
                  width: '100%'
                }} />
            </Box>
          ))}
        </AutoPlaySwipeableViews>

        <MobileStepper
          steps={maxSteps}
          position='static'
          activeStep={activeStep}
          nextButton={null}
          backButton={null}
          sx={{ display: 'flex', justifyContent: 'center', marginTop: '25px' }} />
      </Container>
    </Box>
  );
}

import { Controller, Get, Post, Put, Delete, Body, Param, Query, UseGuards, Request, ParseIntPipe } from '@nestjs/common';
import { ApiBearerAuth, ApiTags, ApiOperation, ApiQuery } from '@nestjs/swagger';
import { LookupsService } from './lookups.service';
import { CreateLookupDto, BulkCreateLookupDto } from './dto/create-lookup.dto';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';

@ApiTags('Lookups - القوائم المرجعية')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard)
@Controller('lookups')
export class LookupsController {
  constructor(private readonly lookupsService: LookupsService) {}

  @Post()
  @ApiOperation({ summary: 'إضافة قيمة مرجعية' })
  create(@Body() dto: CreateLookupDto, @Request() req: any) {
    return this.lookupsService.create(dto, req.user.tenantId);
  }

  @Post('bulk')
  @ApiOperation({ summary: 'إضافة قيم متعددة دفعة واحدة' })
  bulkCreate(@Body() dto: BulkCreateLookupDto, @Request() req: any) {
    return this.lookupsService.bulkCreate(dto, req.user.tenantId);
  }

  @Post('seed')
  @ApiOperation({ summary: 'تعبئة القيم الافتراضية (مدن، بنوك، إلخ)' })
  seed(@Request() req: any) {
    return this.lookupsService.seed(req.user.tenantId);
  }

  @Get('types')
  @ApiOperation({ summary: 'قائمة أنواع القيم المرجعية المتوفرة' })
  getTypes(@Request() req: any) {
    return this.lookupsService.findAllTypes(req.user.tenantId);
  }

  @Get(':type')
  @ApiOperation({ summary: 'قائمة القيم حسب النوع (city, bank, etc.)' })
  findByType(@Param('type') type: string, @Request() req: any) {
    return this.lookupsService.findByType(type, req.user.tenantId);
  }

  @Put(':id')
  @ApiOperation({ summary: 'تعديل قيمة مرجعية' })
  update(@Param('id', ParseIntPipe) id: number, @Body() dto: Partial<CreateLookupDto>, @Request() req: any) {
    return this.lookupsService.update(id, dto, req.user.tenantId);
  }

  @Delete(':id')
  @ApiOperation({ summary: 'تعطيل قيمة مرجعية' })
  remove(@Param('id', ParseIntPipe) id: number, @Request() req: any) {
    return this.lookupsService.remove(id, req.user.tenantId);
  }
}
